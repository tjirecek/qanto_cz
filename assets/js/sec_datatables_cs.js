// datatables_cs.js
// Používá se v datatables.js jako: language: window.QANTO_DT_LANG || {}

window.QANTO_DT_LANG = {
  emptyTable: "Tabulka neobsahuje žádná data",
  info: "Zobrazuji _START_ až _END_ z celkem _TOTAL_ záznamů",
  infoEmpty: "Zobrazuji 0 až 0 z 0 záznamů",
  infoFiltered: "(filtrováno z celkem _MAX_ záznamů)",
  infoThousands: " ",
  lengthMenu: "Zobraz _MENU_",
  loadingRecords: "Načítám...",
  processing: "Provádím...",
  search: "Hledat:",
  zeroRecords: "Žádné záznamy nebyly nalezeny",
  paginate: {
    first: "První",
    last: "Poslední",
    next: "Další",
    previous: "Předchozí"
  },
  aria: {
    sortAscending: ": aktivujte pro řazení sloupce vzestupně",
    sortDescending: ": aktivujte pro řazení sloupce sestupně"
  },

  // doplňky (Buttons, SearchBuilder, Select, AutoFill)
  buttons: {
    colvis: "Zobrazení sloupců",
    colvisRestore: "Původní nastavení",
    collection: "Kolekce",
    copy: "Kopírovat",
    copyKeys: "Stlačte ctrl nebo ⌘ + C pro kopírování dat tabulky do schránky. Pro zrušení stiskněte ESC.",
    copySuccess: {
      1: "Skopírován 1 řádek do schránky",
      _: "Skopírováno %d řádků do schránky"
    },
    copyTitle: "Kopírovat do schránky",
    csv: "CSV",
    excel: "Excel",
    pageLength: {
      "-1": "Všechny řádky",
      1: "1 řádek",
      _: "%d řádků"
    },
    pdf: "PDF",
    print: "Tisknout"
  },

  searchBuilder: {
    add: "Přidat podmínku",
    clearAll: "Smazat vše",
    condition: "Podmínka",
    data: "Sloupec",
    logicAnd: "A",
    logicOr: "NEBO",

    // Když nechceš nadpis "Rozšířený filtr", dej prázdné stringy:
    // title: { 0: "", _: "" },
    title: {
      0: "Rozšířený filtr",
      _: "Rozšířený filtr (%d)"
    },

    value: "Hodnota",
    button: {
      0: "Rozšířený filtr",
      _: "Rozšířený filtr (%d)"
    },
    deleteTitle: "Smazat filtrovací pravidlo"
  },

  select: {
    1: "Vybrán %d záznam",
    2: "Vybrány %d záznamy",
    _: "Vybráno %d záznamů"
  },

  autoFill: {
    cancel: "Zrušit",
    fill: "Vyplnit všechny buňky s <i>%d</i>",
    fillHorizontal: "Vyplnit buňky horizontálně",
    fillVertical: "Vyplnit buňky vertikálně"
  },

  thousands: " "
};

// kompatibilita, kdybys někde starší název používal
window.DataTablesCS = window.QANTO_DT_LANG;