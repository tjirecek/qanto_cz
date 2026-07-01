# 001 Fotogalerie Migrace Report

- Datum: 2026-06-28 00:39:31
- Zdroj DB: `xqanto_cz_old`
- Cil DB: `xqanto_cz_main`
- Zdroj souboru: `/Users/tjirecek/www_dev/qanto_cz/media/galerie`, `/Users/tjirecek/www_dev/old-qanto_cz/_images/_galerie`
- Cil souboru: `/Users/tjirecek/www_dev/qanto_cz/media/galerie`
- Zdroj je cilove `media/galerie`: ano, reset maze jen `small` podslozky
- Rezim: dry-run
- Reset cilovych tabulek: ne

## Pocty

| Oblast | Old | Cil pred | Cil po |
| --- | ---: | ---: | ---: |
| `galerie_typ` | 6 | 6 | 6 |
| `galerie` | 94 | 94 | 94 |
| `galerie_photo` | 7909 | 863 | 863 |

## Fotky

- Vlozeno fotek: 863
- Zkopirovano/zpracovano souboru: 863
- Preskoceno fotek: 7046
- Chybejici soubory: 7045
- Duplicitni fotky podle galerie/souboru: 1
- Chyby zpracovani obrazku: 0

## Nejvice zpracovanych galerii

| Galerie ID | Fotky |
| ---: | ---: |

## Nejvice chybejicich souboru

| Galerie ID | Chybi |
| ---: | ---: |
| 69 | 696 |
| 71 | 607 |
| 65 | 542 |
| 78 | 542 |
| 67 | 540 |
| 70 | 518 |
| 80 | 425 |
| 62 | 420 |
| 90 | 411 |
| 58 | 375 |
| 87 | 327 |
| 81 | 276 |
| 82 | 252 |
| 74 | 82 |
| 64 | 72 |
| 66 | 64 |
| 85 | 63 |
| 76 | 62 |
| 63 | 60 |
| 92 | 59 |
| 75 | 57 |
| 72 | 56 |
| 79 | 56 |
| 60 | 49 |
| 88 | 39 |
| 61 | 35 |
| 77 | 35 |
| 15 | 33 |
| 83 | 33 |
| 84 | 33 |

## Duplicitni Fotky

| Photo ID | Galerie ID | Soubor | Ponechano Photo ID |
| ---: | ---: | --- | ---: |
| 630 | 51 | `img_0548.jpg` | 629 |

## Chybejici Soubory - prvnich 300

| Photo ID | Galerie ID | Soubor |
| ---: | ---: | --- |
| 2145 | 3 | `avx-1-.jpg` |
| 2146 | 3 | `avx-3-.jpg` |
| 2147 | 3 | `avx-4-.jpg` |
| 2148 | 3 | `avx-5-.jpg` |
| 2149 | 3 | `avx-6-.jpg` |
| 2150 | 3 | `avx-7-.jpg` |
| 2151 | 3 | `avx-8-.jpg` |
| 2152 | 3 | `avx-9-.jpg` |
| 10625 | 4 | `IMG_1464-37 ano.jpg` |
| 10626 | 4 | `IMG_1425-20 ano.jpg` |
| 10627 | 4 | `IMG_1420-16 ano.jpg` |
| 10628 | 4 | `IMG_1423-19 ano.jpg` |
| 10629 | 4 | `IMG_1441-26 ano.jpg` |
| 10630 | 4 | `IMG_1460-36 ano.jpg` |
| 10631 | 4 | `IMG_1416-14 ano.jpg` |
| 10632 | 4 | `IMG_1395-2 ano.jpg` |
| 10633 | 4 | `IMG_1414-12 ano.jpg` |
| 10634 | 4 | `IMG_1406-7 ano.jpg` |
| 9663 | 15 | `002-chrastova-202107.jpg` |
| 9675 | 15 | `004-chrastova-202107.jpg` |
| 9672 | 15 | `005-chrastova-202107.jpg` |
| 9665 | 15 | `007-chrastova-202107.jpg` |
| 9673 | 15 | `008-chrastova-202107.jpg` |
| 9677 | 15 | `010-chrastova-202107.jpg` |
| 9670 | 15 | `011-chrastova-202107.jpg` |
| 9664 | 15 | `012-chrastova-202107.jpg` |
| 9666 | 15 | `015-chrastova-202107.jpg` |
| 9669 | 15 | `016-chrastova-202107.jpg` |
| 9668 | 15 | `017-chrastova-202107.jpg` |
| 9674 | 15 | `018-chrastova-202107.jpg` |
| 9667 | 15 | `019-chrastova-202107.jpg` |
| 9688 | 15 | `020-chrastova-202107.jpg` |
| 9689 | 15 | `021-chrastova-202107.jpg` |
| 9691 | 15 | `022-chrastova-202107.jpg` |
| 9676 | 15 | `023-chrastova-202107.jpg` |
| 9690 | 15 | `024-chrastova-202107.jpg` |
| 9687 | 15 | `026-chrastova-202107.jpg` |
| 9685 | 15 | `027-chrastova-202107.jpg` |
| 9684 | 15 | `030-chrastova-202107.jpg` |
| 9681 | 15 | `032-chrastova-202107.jpg` |
| 9683 | 15 | `034-chrastova-202107.jpg` |
| 9671 | 15 | `035-chrastova-202107.jpg` |
| 9679 | 15 | `036-chrastova-202107.jpg` |
| 9678 | 15 | `037-chrastova-202107.jpg` |
| 9680 | 15 | `038-chrastova-202107.jpg` |
| 9682 | 15 | `040-chrastova-202107.jpg` |
| 9694 | 15 | `042-chrastova-202107.jpg` |
| 9692 | 15 | `043-chrastova-202107.jpg` |
| 9693 | 15 | `045-chrastova-202107.jpg` |
| 9695 | 15 | `046-chrastova-202107.jpg` |
| 9686 | 15 | `048-chrastova-202107.jpg` |
| 10615 | 16 | `IMG_1377-28 ano.jpg` |
| 10616 | 16 | `IMG_1337-3 ano.jpg` |
| 10617 | 16 | `IMG_1372-24 ano.jpg` |
| 10618 | 16 | `IMG_1335-2 ano.jpg` |
| 10619 | 16 | `IMG_1374-25 ano.jpg` |
| 10620 | 16 | `IMG_1366-20 ano.jpg` |
| 10621 | 16 | `IMG_1342-7 ano.jpg` |
| 10622 | 16 | `IMG_1350-11 ano.jpg` |
| 10623 | 16 | `IMG_1360-16 ano.jpg` |
| 10624 | 16 | `IMG_1347-10 ano.jpg` |
| 8983 | 17 | `201910-01-brezova.jpg` |
| 8978 | 17 | `201910-02-brezova.jpg` |
| 8984 | 17 | `201910-03-brezova.jpg` |
| 8982 | 17 | `201910-04-brezova.jpg` |
| 8979 | 17 | `201910-05-brezova.jpg` |
| 8980 | 17 | `201910-06-brezova.jpg` |
| 8981 | 17 | `201910-07-brezova.jpg` |
| 8986 | 17 | `201910-08-brezova.jpg` |
| 8985 | 17 | `201910-09-brezova.jpg` |
| 8988 | 17 | `201910-10-brezova.jpg` |
| 8987 | 17 | `201910-11-brezova.jpg` |
| 8989 | 17 | `201910-12-brezova.jpg` |
| 8991 | 17 | `201910-13-brezova.jpg` |
| 8990 | 17 | `201910-14-brezova.jpg` |
| 8993 | 17 | `201910-15-brezova.jpg` |
| 8994 | 17 | `201910-16-brezova.jpg` |
| 8992 | 17 | `201910-17-brezova.jpg` |
| 8995 | 17 | `201910-18-brezova.jpg` |
| 8996 | 17 | `201910-19-brezova.jpg` |
| 8998 | 17 | `201910-20-brezova.jpg` |
| 8997 | 17 | `201910-21-brezova.jpg` |
| 8999 | 17 | `201910-22-brezova.jpg` |
| 9000 | 17 | `201910-23-brezova.jpg` |
| 9001 | 17 | `201910-24-brezova.jpg` |
| 9002 | 17 | `201910-25-brezova.jpg` |
| 2131 | 18 | `koclirov-1-.jpg` |
| 2132 | 18 | `koclirov-2-.jpg` |
| 2133 | 18 | `koclirov-3-.jpg` |
| 2134 | 18 | `koclirov-4-.jpg` |
| 2135 | 18 | `koclirov-5-.jpg` |
| 2136 | 18 | `koclirov-6-.jpg` |
| 2137 | 18 | `koclirov-7-.jpg` |
| 2138 | 18 | `koclirov-8-.jpg` |
| 2139 | 18 | `koclirov-9-.jpg` |
| 2142 | 18 | `koclirov-12-.jpg` |
| 2143 | 18 | `koclirov-14-.jpg` |
| 2144 | 18 | `koclirov-15-.jpg` |
| 2100 | 19 | `2014-04-24-qantovendoli.jpg` |
| 2102 | 19 | `2014-04-qantovendolii.jpg` |
| 2103 | 19 | `2014_vendoli-1-.jpg` |
| 2104 | 19 | `2014_vendoli-2-.jpg` |
| 2105 | 19 | `2014_vendoli-3-.jpg` |
| 2106 | 19 | `2014_vendoli-4-.jpg` |
| 2107 | 19 | `2014_vendoli-5-.jpg` |
| 2108 | 19 | `2014_vendoli-6-.jpg` |
| 2109 | 19 | `2014_vendoli-7-.jpg` |
| 2110 | 19 | `2014_vendoli-8-.jpg` |
| 2111 | 19 | `2014_vendoli-9-.jpg` |
| 2112 | 19 | `2014_vendoli-10-.jpg` |
| 2113 | 19 | `2014_vendoli-11-.jpg` |
| 2114 | 22 | `dolni-cermna-1-.jpg` |
| 2115 | 22 | `dolni-cermna-2-.jpg` |
| 2116 | 22 | `dolni-cermna-3-.jpg` |
| 2117 | 22 | `dolni-cermna-5-.jpg` |
| 2118 | 22 | `dolni-cermna-6-.jpg` |
| 2119 | 22 | `dolni-cermna-7-.jpg` |
| 2120 | 22 | `dolni-cermna-8-.jpg` |
| 2121 | 22 | `dolni-cermna-9-.jpg` |
| 2122 | 22 | `dolni-cermna-10-.jpg` |
| 2123 | 22 | `dolni-cermna-11-.jpg` |
| 2124 | 22 | `dolni-cermna-12-.jpg` |
| 2125 | 22 | `dolni-cermna-13-.jpg` |
| 2126 | 22 | `dolni-cermna-15-.jpg` |
| 2127 | 22 | `dolni-cermna-16-.jpg` |
| 2128 | 22 | `dolni-cermna-17-.jpg` |
| 2129 | 22 | `dolni-cermna-18-.jpg` |
| 2130 | 22 | `dolni-cermna-19-.jpg` |
| 10594 | 52 | `11-travnik-rohlik.jpg` |
| 10595 | 52 | `12-travnik-zelenina.jpg` |
| 10596 | 52 | `13-marketchocen.jpg` |
| 10597 | 52 | `15-travnik-remeslnyroh.jpg` |
| 10598 | 52 | `14-travnik-pecivo.jpg` |
| 10599 | 52 | `13-travnik-ovoce.jpg` |
| 1070 | 57 | `sokoltrebes-4.jpg` |
| 1071 | 57 | `jiskravir-1.jpg` |
| 1072 | 57 | `fk-lomnice-4.jpg` |
| 1073 | 57 | `tj-sokol-bystre-2-.jpg` |
| 1074 | 57 | `tj-sokol-bystre-3-.jpg` |
| 1075 | 57 | `tj-sokol-bystre-4-.jpg` |
| 1076 | 57 | `tj-sokol-bystre-5-.jpg` |
| 1077 | 58 | `1-vecirek-1-2013.jpg` |
| 1078 | 58 | `2-vecirek-1-2013.jpg` |
| 1079 | 58 | `3-vecirek-1-2013.jpg` |
| 1081 | 58 | `5-vecirek-1-2013.jpg` |
| 1082 | 58 | `6-vecirek-1-2013.jpg` |
| 1083 | 58 | `7-vecirek-1-2013.jpg` |
| 1084 | 58 | `8-vecirek-1-2013.jpg` |
| 1088 | 58 | `12-vecirek-1-2013.jpg` |
| 1089 | 58 | `13-vecirek-1-2013.jpg` |
| 1090 | 58 | `14-vecirek-1-2013.jpg` |
| 1091 | 58 | `15-vecirek-1-2013.jpg` |
| 1092 | 58 | `16-vecirek-1-2013.jpg` |
| 1093 | 58 | `17-vecirek-1-2013.jpg` |
| 1094 | 58 | `18-vecirek-1-2013.jpg` |
| 1096 | 58 | `20-vecirek-1-2013.jpg` |
| 1098 | 58 | `22-vecirek-1-2013.jpg` |
| 1099 | 58 | `23-vecirek-1-2013.jpg` |
| 1100 | 58 | `24-vecirek-1-2013.jpg` |
| 1101 | 58 | `25-vecirek-1-2013.jpg` |
| 1102 | 58 | `26-vecirek-1-2013.jpg` |
| 1103 | 58 | `27-vecirek-1-2013.jpg` |
| 1104 | 58 | `28-vecirek-1-2013.jpg` |
| 1105 | 58 | `29-vecirek-1-2013.jpg` |
| 1106 | 58 | `30-vecirek-1-2013.jpg` |
| 1110 | 58 | `34-vecirek-1-2013.jpg` |
| 1111 | 58 | `35-vecirek-1-2013.jpg` |
| 1112 | 58 | `36-vecirek-1-2013.jpg` |
| 1113 | 58 | `37-vecirek-1-2013.jpg` |
| 1114 | 58 | `38-vecirek-1-2013.jpg` |
| 1115 | 58 | `39-vecirek-1-2013.jpg` |
| 1119 | 58 | `43-vecirek-1-2013.jpg` |
| 1120 | 58 | `44-vecirek-1-2013.jpg` |
| 1121 | 58 | `45-vecirek-1-2013.jpg` |
| 1122 | 58 | `46-vecirek-1-2013.jpg` |
| 1123 | 58 | `47-vecirek-1-2013.jpg` |
| 1124 | 58 | `48-vecirek-1-2013.jpg` |
| 1125 | 58 | `49-vecirek-1-2013.jpg` |
| 1126 | 58 | `50-vecirek-1-2013.jpg` |
| 1128 | 58 | `52-vecirek-1-2013.jpg` |
| 1129 | 58 | `53-vecirek-1-2013.jpg` |
| 1130 | 58 | `54-vecirek-1-2013.jpg` |
| 1131 | 58 | `55-vecirek-1-2013.jpg` |
| 1132 | 58 | `56-vecirek-1-2013.jpg` |
| 1133 | 58 | `57-vecirek-1-2013.jpg` |
| 1134 | 58 | `58-vecirek-1-2013.jpg` |
| 1135 | 58 | `59-vecirek-1-2013.jpg` |
| 1136 | 58 | `60-vecirek-1-2013.jpg` |
| 1137 | 58 | `61-vecirek-1-2013.jpg` |
| 1138 | 58 | `62-vecirek-1-2013.jpg` |
| 1139 | 58 | `63-vecirek-1-2013.jpg` |
| 1140 | 58 | `64-vecirek-1-2013.jpg` |
| 1141 | 58 | `65-vecirek-1-2013.jpg` |
| 1142 | 58 | `66-vecirek-1-2013.jpg` |
| 1143 | 58 | `67-vecirek-1-2013.jpg` |
| 1144 | 58 | `68-vecirek-1-2013.jpg` |
| 1145 | 58 | `69-vecirek-1-2013.jpg` |
| 1146 | 58 | `70-vecirek-1-2013.jpg` |
| 1147 | 58 | `71-vecirek-1-2013.jpg` |
| 1148 | 58 | `72-vecirek-1-2013.jpg` |
| 1149 | 58 | `73-vecirek-1-2013.jpg` |
| 1150 | 58 | `74-vecirek-1-2013.jpg` |
| 1151 | 58 | `75-vecirek-1-2013.jpg` |
| 1152 | 58 | `76-vecirek-1-2013.jpg` |
| 1153 | 58 | `77-vecirek-1-2013.jpg` |
| 1154 | 58 | `78-vecirek-1-2013.jpg` |
| 1156 | 58 | `80-vecirek-1-2013.jpg` |
| 1158 | 58 | `82-vecirek-1-2013.jpg` |
| 1160 | 58 | `84-vecirek-1-2013.jpg` |
| 1161 | 58 | `85-vecirek-1-2013.jpg` |
| 1162 | 58 | `86-vecirek-1-2013.jpg` |
| 1163 | 58 | `87-vecirek-1-2013.jpg` |
| 1164 | 58 | `88-vecirek-1-2013.jpg` |
| 1165 | 58 | `89-vecirek-1-2013.jpg` |
| 1167 | 58 | `91-vecirek-1-2013.jpg` |
| 1168 | 58 | `92-vecirek-1-2013.jpg` |
| 1169 | 58 | `93-vecirek-1-2013.jpg` |
| 1170 | 58 | `94-vecirek-1-2013.jpg` |
| 1171 | 58 | `95-vecirek-1-2013.jpg` |
| 1172 | 58 | `96-vecirek-1-2013.jpg` |
| 1173 | 58 | `97-vecirek-1-2013.jpg` |
| 1174 | 58 | `98-vecirek-1-2013.jpg` |
| 1175 | 58 | `99-vecirek-1-2013.jpg` |
| 1176 | 58 | `100-vecirek-1-2013.jpg` |
| 1177 | 58 | `101-vecirek-1-2013.jpg` |
| 1178 | 58 | `102-vecirek-1-2013.jpg` |
| 1180 | 58 | `104-vecirek-1-2013.jpg` |
| 1181 | 58 | `105-vecirek-1-2013.jpg` |
| 1182 | 58 | `106-vecirek-1-2013.jpg` |
| 1183 | 58 | `107-vecirek-1-2013.jpg` |
| 1184 | 58 | `108-vecirek-1-2013.jpg` |
| 1185 | 58 | `109-vecirek-1-2013.jpg` |
| 1186 | 58 | `110-vecirek-1-2013.jpg` |
| 1187 | 58 | `111-vecirek-1-2013.jpg` |
| 1188 | 58 | `112-vecirek-1-2013.jpg` |
| 1189 | 58 | `113-vecirek-1-2013.jpg` |
| 1190 | 58 | `114-vecirek-1-2013.jpg` |
| 1191 | 58 | `115-vecirek-1-2013.jpg` |
| 1192 | 58 | `116-vecirek-1-2013.jpg` |
| 1193 | 58 | `117-vecirek-1-2013.jpg` |
| 1194 | 58 | `118-vecirek-1-2013.jpg` |
| 1195 | 58 | `119-vecirek-1-2013.jpg` |
| 1196 | 58 | `120-vecirek-1-2013.jpg` |
| 1197 | 58 | `121-vecirek-1-2013.jpg` |
| 1198 | 58 | `122-vecirek-1-2013.jpg` |
| 1199 | 58 | `123-vecirek-1-2013.jpg` |
| 1200 | 58 | `124-vecirek-1-2013.jpg` |
| 1201 | 58 | `125-vecirek-1-2013.jpg` |
| 1202 | 58 | `126-vecirek-1-2013.jpg` |
| 1204 | 58 | `128-vecirek-1-2013.jpg` |
| 1205 | 58 | `129-vecirek-1-2013.jpg` |
| 1206 | 58 | `130-vecirek-1-2013.jpg` |
| 1209 | 58 | `133-vecirek-1-2013.jpg` |
| 1210 | 58 | `134-vecirek-1-2013.jpg` |
| 1211 | 58 | `135-vecirek-1-2013.jpg` |
| 1212 | 58 | `136-vecirek-1-2013.jpg` |
| 1214 | 58 | `138-vecirek-1-2013.jpg` |
| 1215 | 58 | `139-vecirek-1-2013.jpg` |
| 1216 | 58 | `140-vecirek-1-2013.jpg` |
| 1217 | 58 | `141-vecirek-1-2013.jpg` |
| 1218 | 58 | `142-vecirek-1-2013.jpg` |
| 1219 | 58 | `143-vecirek-1-2013.jpg` |
| 1220 | 58 | `144-vecirek-1-2013.jpg` |
| 1221 | 58 | `145-vecirek-1-2013.jpg` |
| 1222 | 58 | `146-vecirek-1-2013.jpg` |
| 1223 | 58 | `147-vecirek-1-2013.jpg` |
| 1224 | 58 | `148-vecirek-1-2013.jpg` |
| 1225 | 58 | `149-vecirek-1-2013.jpg` |
| 1226 | 58 | `150-vecirek-1-2013.jpg` |
| 1227 | 58 | `151-vecirek-1-2013.jpg` |
| 1228 | 58 | `152-vecirek-1-2013.jpg` |
| 1229 | 58 | `153-vecirek-1-2013.jpg` |
| 1230 | 58 | `154-vecirek-1-2013.jpg` |
| 1231 | 58 | `155-vecirek-1-2013.jpg` |
| 1232 | 58 | `156-vecirek-1-2013.jpg` |
| 1233 | 58 | `157-vecirek-1-2013.jpg` |
| 1234 | 58 | `158-vecirek-1-2013.jpg` |
| 1235 | 58 | `159-vecirek-1-2013.jpg` |
| 1236 | 58 | `160-vecirek-1-2013.jpg` |
| 1239 | 58 | `163-vecirek-1-2013.jpg` |
| 1240 | 58 | `164-vecirek-1-2013.jpg` |
| 1241 | 58 | `165-vecirek-1-2013.jpg` |
| 1242 | 58 | `166-vecirek-1-2013.jpg` |
| 1243 | 58 | `167-vecirek-1-2013.jpg` |
| 1244 | 58 | `168-vecirek-1-2013.jpg` |
| 1245 | 58 | `169-vecirek-1-2013.jpg` |
| 1246 | 58 | `170-vecirek-1-2013.jpg` |
| 1247 | 58 | `171-vecirek-1-2013.jpg` |
| 1248 | 58 | `172-vecirek-1-2013.jpg` |
| 1249 | 58 | `173-vecirek-1-2013.jpg` |
| 1250 | 58 | `174-vecirek-1-2013.jpg` |
| 1251 | 58 | `175-vecirek-1-2013.jpg` |
| 1252 | 58 | `176-vecirek-1-2013.jpg` |
| 1253 | 58 | `177-vecirek-1-2013.jpg` |
| 1254 | 58 | `178-vecirek-1-2013.jpg` |
| 1255 | 58 | `179-vecirek-1-2013.jpg` |
| 1256 | 58 | `180-vecirek-1-2013.jpg` |
| 1257 | 58 | `181-vecirek-1-2013.jpg` |
| 1258 | 58 | `182-vecirek-1-2013.jpg` |
| 1259 | 58 | `183-vecirek-1-2013.jpg` |
