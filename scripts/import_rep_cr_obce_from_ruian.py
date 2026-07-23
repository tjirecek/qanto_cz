#!/usr/bin/env python3
"""Import RUIAN address CSV ZIP into qanto.cz project reference tables.

Runtime-only helper for local/project data preparation. It expects the RUIAN
CSV address ZIP downloaded from the official CUZK Atom feed.
"""

from __future__ import annotations

import argparse
import csv
import os
import re
import unicodedata
import zipfile
from dataclasses import dataclass, field

import pymysql
from pyproj import Transformer


@dataclass
class ObecAggregate:
    kod_obce: str
    nazev: str
    okres: str = ""
    kraj: str = ""
    orp: str = ""
    psc: set[str] = field(default_factory=set)
    x_sum: float = 0.0
    y_sum: float = 0.0
    count: int = 0


def normalize_name(value: str) -> str:
    value = unicodedata.normalize("NFKD", value.strip().lower())
    value = "".join(ch for ch in value if not unicodedata.combining(ch))
    return re.sub(r"\s+", " ", value)


def normalize_psc(value: str) -> str:
    psc = re.sub(r"\D+", "", value or "")
    return psc if len(psc) == 5 else ""


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--zip", required=True, help="Path to RUIAN CSV address ZIP")
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="root")
    parser.add_argument("--database", default="xqanto_cz_main")
    parser.add_argument("--source", default="RUIAN-CSV-ADR-ST 20260630")
    parser.add_argument("--hierarchy-zip", help="Path to RUIAN CSV-HIE-ST hierarchy ZIP")
    parser.add_argument("--okres-csv", help="CSU iSMS export for ciselnik 101")
    parser.add_argument("--kraj-csv", help="CSU iSMS export for ciselnik 100")
    parser.add_argument("--orp-csv", help="CSU iSMS export for ciselnik 65")
    return parser.parse_args()


def load_csu_ruian_names(path: str | None) -> dict[str, str]:
    if not path:
        return {}
    if not os.path.isfile(path):
        raise SystemExit(f"CSU CSV not found: {path}")

    names: dict[str, str] = {}
    with open(path, newline="", encoding="utf-8-sig") as handle:
        reader = csv.DictReader(handle)
        for row in reader:
            kod_ruian = (row.get("kod_ruian") or "").strip()
            text = (row.get("text") or row.get("zkrtext") or "").strip()
            if kod_ruian and text:
                names[kod_ruian] = text
    return names


def load_hierarchy(
    zip_path: str | None,
    okres_names: dict[str, str],
    kraj_names: dict[str, str],
    orp_names: dict[str, str],
) -> dict[str, dict[str, str]]:
    if not zip_path:
        return {}
    if not os.path.isfile(zip_path):
        raise SystemExit(f"Hierarchy ZIP not found: {zip_path}")

    hierarchy: dict[str, dict[str, str]] = {}
    with zipfile.ZipFile(zip_path) as archive:
        name = next((item for item in archive.namelist() if item.endswith("vazby-cr.csv")), "")
        if not name:
            raise SystemExit("vazby-cr.csv not found in hierarchy ZIP")

        with archive.open(name) as raw:
            reader = csv.DictReader((line.decode("cp1250") for line in raw), delimiter=";")
            for row in reader:
                kod_obce = (row.get("OBEC_KOD") or "").strip()
                if not kod_obce or kod_obce in hierarchy:
                    continue

                okres = okres_names.get((row.get("OKRES_KOD") or "").strip(), "")
                kraj = kraj_names.get((row.get("VUSC_KOD") or "").strip(), "")
                hierarchy[kod_obce] = {
                    "okres": okres or kraj,
                    "kraj": kraj,
                    "orp": orp_names.get((row.get("ORP_KOD") or "").strip(), ""),
                }
    return hierarchy


def load_aggregates(zip_path: str, hierarchy: dict[str, dict[str, str]]) -> dict[str, ObecAggregate]:
    aggregates: dict[str, ObecAggregate] = {}
    with zipfile.ZipFile(zip_path) as archive:
        names = [name for name in archive.namelist() if name.lower().endswith(".csv")]
        for index, name in enumerate(names, start=1):
            if index % 500 == 0:
                print(f"read_csv={index}/{len(names)}")

            with archive.open(name) as raw:
                text = (line.decode("cp1250") for line in raw)
                reader = csv.reader(text, delimiter=";")
                header = next(reader, None)
                if not header:
                    continue

                for row in reader:
                    if len(row) < 18:
                        continue

                    kod_obce = row[1].strip()
                    nazev = row[2].strip()
                    psc = normalize_psc(row[15])
                    try:
                        y = float(row[16].replace(",", "."))
                        x = float(row[17].replace(",", "."))
                    except ValueError:
                        continue

                    if kod_obce == "" or nazev == "":
                        continue

                    aggregate = aggregates.get(kod_obce)
                    if aggregate is None:
                        location = hierarchy.get(kod_obce, {})
                        aggregate = ObecAggregate(
                            kod_obce=kod_obce,
                            nazev=nazev,
                            okres=location.get("okres", ""),
                            kraj=location.get("kraj", ""),
                            orp=location.get("orp", ""),
                        )
                        aggregates[kod_obce] = aggregate

                    if psc:
                        aggregate.psc.add(psc)
                    aggregate.x_sum += x
                    aggregate.y_sum += y
                    aggregate.count += 1

    return aggregates


def import_to_db(args: argparse.Namespace, aggregates: dict[str, ObecAggregate]) -> tuple[int, int]:
    transformer = Transformer.from_crs("EPSG:5513", "EPSG:4326", always_xy=True)
    connection = pymysql.connect(
        host=args.host,
        port=args.port,
        user=args.user,
        password=args.password,
        database=args.database,
        charset="utf8mb4",
        autocommit=False,
    )

    try:
        with connection.cursor() as cursor:
            cursor.execute("UPDATE rep_cr_obce_psc SET valid = 0, user_u = %s", ("ruian-import",))
            cursor.execute("UPDATE rep_cr_obce SET valid = 0, user_u = %s", ("ruian-import",))

            okres_ids: dict[tuple[str, str], int] = {}
            okres_sql = """
                INSERT INTO rep_cr_okresy
                    (nazev, kraj, valid, user_i, user_u)
                VALUES
                    (%s, %s, 1, 'ruian-import', 'ruian-import')
                ON DUPLICATE KEY UPDATE
                    valid = 1,
                    user_u = VALUES(user_u)
            """
            for okres, kraj in sorted({
                (aggregate.okres, aggregate.kraj)
                for aggregate in aggregates.values()
                if aggregate.okres
            }):
                cursor.execute(okres_sql, (okres, kraj or None))
                cursor.execute(
                    "SELECT id FROM rep_cr_okresy WHERE nazev = %s AND kraj <=> %s LIMIT 1",
                    (okres, kraj or None),
                )
                okres_ids[(okres, kraj)] = int(cursor.fetchone()[0])

            obec_sql = """
                INSERT INTO rep_cr_obce
                    (kod_obce, nazev, nazev_normalized, okres, okres_id, kraj, orp, psc_list, lat, lng, source, valid, user_i, user_u)
                VALUES
                    (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, 1, 'ruian-import', 'ruian-import')
                ON DUPLICATE KEY UPDATE
                    nazev = VALUES(nazev),
                    nazev_normalized = VALUES(nazev_normalized),
                    okres = VALUES(okres),
                    okres_id = VALUES(okres_id),
                    kraj = VALUES(kraj),
                    orp = VALUES(orp),
                    psc_list = VALUES(psc_list),
                    lat = VALUES(lat),
                    lng = VALUES(lng),
                    source = VALUES(source),
                    valid = 1,
                    user_u = VALUES(user_u)
            """
            psc_sql = """
                INSERT INTO rep_cr_obce_psc
                    (obec_id, psc, valid, user_i, user_u)
                VALUES
                    (%s, %s, 1, 'ruian-import', 'ruian-import')
                ON DUPLICATE KEY UPDATE
                    valid = 1,
                    user_u = VALUES(user_u)
            """

            obec_count = 0
            psc_count = 0
            for aggregate in aggregates.values():
                if aggregate.count <= 0:
                    continue

                avg_x = aggregate.x_sum / aggregate.count
                avg_y = aggregate.y_sum / aggregate.count
                lng, lat = transformer.transform(avg_x, avg_y)
                psc_list = ", ".join(sorted(aggregate.psc))

                cursor.execute(
                    obec_sql,
                    (
                        aggregate.kod_obce,
                        aggregate.nazev,
                        normalize_name(aggregate.nazev),
                        aggregate.okres or None,
                        okres_ids.get((aggregate.okres, aggregate.kraj)),
                        aggregate.kraj or None,
                        aggregate.orp or None,
                        psc_list,
                        round(lat, 7),
                        round(lng, 7),
                        args.source,
                    ),
                )
                cursor.execute("SELECT id FROM rep_cr_obce WHERE kod_obce = %s", (aggregate.kod_obce,))
                obec_id = int(cursor.fetchone()[0])
                obec_count += 1

                for psc in sorted(aggregate.psc):
                    cursor.execute(psc_sql, (obec_id, psc))
                    psc_count += 1

            connection.commit()
            return obec_count, psc_count
    except Exception:
        connection.rollback()
        raise
    finally:
        connection.close()


def main() -> None:
    args = parse_args()
    if not os.path.isfile(args.zip):
        raise SystemExit(f"ZIP not found: {args.zip}")

    hierarchy = load_hierarchy(
        args.hierarchy_zip,
        load_csu_ruian_names(args.okres_csv),
        load_csu_ruian_names(args.kraj_csv),
        load_csu_ruian_names(args.orp_csv),
    )
    aggregates = load_aggregates(args.zip, hierarchy)
    obec_count, psc_count = import_to_db(args, aggregates)
    print(f"obce={obec_count}")
    print(f"psc_vazby={psc_count}")


if __name__ == "__main__":
    main()
