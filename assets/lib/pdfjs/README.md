# Mozilla PDF.js

Vendored browser build used by the Qanto action-offer administration to render
uploaded PDF files into image pages without a server-side PDF renderer.

- Package: `pdfjs-dist`
- Version: `6.2.108`
- Build: `legacy/build/pdf.min.mjs` and `legacy/build/pdf.worker.min.mjs`
- License: Apache-2.0, see `LICENSE`
- Source: https://github.com/mozilla/pdf.js

The `cmaps`, `iccs`, `standard_fonts`, and `wasm` directories come from the
same npm package version and are required for general PDF compatibility.
