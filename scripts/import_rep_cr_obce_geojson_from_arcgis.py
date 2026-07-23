#!/usr/bin/env python3
"""Import simplified RUIAN municipality polygons into rep_cr_obce.geojson.

Runtime-only helper for local/project data preparation. Source is the public
ArcGIS REST RUIAN layer with municipality polygons; geometries are stored as
GeoJSON geometry objects keyed by rep_cr_obce.kod_obce.
"""

from __future__ import annotations

import argparse
import json
import time
import urllib.parse
import urllib.request
from typing import Any

import pymysql

SERVICE_URL = "https://geoportal.mzcr.cz/server/rest/services/RUIAN/Dynamic/MapServer/0/query"


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser()
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=3306)
    parser.add_argument("--user", default="root")
    parser.add_argument("--password", default="root")
    parser.add_argument("--database", default="xqanto_cz_main")
    parser.add_argument("--batch-size", type=int, default=1000)
    parser.add_argument("--offset", default="0.001", help="ArcGIS maxAllowableOffset in EPSG:4326 degrees")
    parser.add_argument("--precision", default="6", help="ArcGIS geometryPrecision")
    parser.add_argument("--sleep", type=float, default=0.1)
    return parser.parse_args()


def fetch_geojson(offset: int, limit: int, simplify_offset: str, precision: str) -> dict[str, Any]:
    params = {
        "where": "1=1",
        "outFields": "Kod,Nazev",
        "returnGeometry": "true",
        "outSR": "4326",
        "maxAllowableOffset": simplify_offset,
        "geometryPrecision": precision,
        "resultOffset": str(offset),
        "resultRecordCount": str(limit),
        "f": "geojson",
    }
    url = SERVICE_URL + "?" + urllib.parse.urlencode(params)
    with urllib.request.urlopen(url, timeout=120) as response:
        raw = response.read().decode("utf-8")
    data = json.loads(raw)
    if "error" in data:
        raise RuntimeError(json.dumps(data["error"], ensure_ascii=False))
    return data


def fetch_count() -> int:
    params = {
        "where": "1=1",
        "returnCountOnly": "true",
        "f": "json",
    }
    url = SERVICE_URL + "?" + urllib.parse.urlencode(params)
    with urllib.request.urlopen(url, timeout=60) as response:
        data = json.loads(response.read().decode("utf-8"))
    return int(data["count"])


def import_to_db(args: argparse.Namespace) -> tuple[int, int, int]:
    total = fetch_count()
    connection = pymysql.connect(
        host=args.host,
        port=args.port,
        user=args.user,
        password=args.password,
        database=args.database,
        charset="utf8mb4",
        autocommit=False,
    )

    matched = 0
    missing = 0
    seen = 0
    update_sql = """
        UPDATE rep_cr_obce
        SET geojson = %s, user_u = 'ruian-geojson-import'
        WHERE kod_obce = %s AND valid = 1
    """

    try:
        with connection.cursor() as cursor:
            cursor.execute("UPDATE rep_cr_obce SET geojson = NULL WHERE valid = 1")
            for offset in range(0, total, args.batch_size):
                data = fetch_geojson(offset, args.batch_size, args.offset, args.precision)
                features = data.get("features") or []
                if not features:
                    break

                for feature in features:
                    props = feature.get("properties") or {}
                    geom = feature.get("geometry")
                    kod = str(props.get("Kod") or "").strip()
                    if not kod or not geom:
                        continue

                    seen += 1
                    geom_json = json.dumps(geom, ensure_ascii=False, separators=(",", ":"))
                    cursor.execute(update_sql, (geom_json, kod))
                    if cursor.rowcount > 0:
                        matched += 1
                    else:
                        missing += 1

                connection.commit()
                print(f"offset={offset} features={len(features)} seen={seen} matched={matched} missing={missing}")
                if len(features) < args.batch_size:
                    break
                if args.sleep > 0:
                    time.sleep(args.sleep)
    except Exception:
        connection.rollback()
        raise
    finally:
        connection.close()

    return seen, matched, missing


def main() -> None:
    args = parse_args()
    seen, matched, missing = import_to_db(args)
    print(f"seen={seen}")
    print(f"matched={matched}")
    print(f"missing={missing}")


if __name__ == "__main__":
    main()
