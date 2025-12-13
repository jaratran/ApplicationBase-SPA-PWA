/**
 * --------------------------------------------------------------------------
 * generate-sw-manifest.js
 * --------------------------------------------------------------------------
 * Este script genera automáticamente el archivo:
 *     /public/build/manifest-sw.js
 *
 * El objetivo es construir una lista completa de archivos para precachear
 * en el Service Worker leyendo el manifest.json generado por Vite.
 *
 * Esto permite:
 *   - Precachear todos los bundles JS, CSS y assets sin escribirlos a mano.
 *   - Evitar errores por cambios de nombres con hash en cada build.
 *   - Mantener el Service Worker siempre sincronizado con el build real.
 *
 * El archivo final exporta:
 *     self.__PRECACHE = [ ... ];
 *
 * El Service Worker luego lo carga mediante:
 *     importScripts("/build/manifest-sw.js");
 *
 * Este script se ejecuta automáticamente después de `vite build` mediante:
 *     "build": "vite build && node scripts/generate-sw-manifest.js"
 *
 * Requiere Node.js, ya que utiliza fs para leer y escribir archivos.
 *
  * Se ejecuta como ES Module porque en package.json tenemos:
 *     "type": "module"*
 */

import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";

// Emular __dirname en ESM
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Ruta absoluta al manifest.json generado por Vite
const manifestPath = path.resolve(__dirname, "../public/build/manifest.json");

// Leer y parsear el manifest
const manifest = JSON.parse(fs.readFileSync(manifestPath, "utf8"));

let precache = [
	"/",
	"/manifest.json",
	"/config/pwa/icon-192.png",
	"/config/pwa/icon-512.png",

	// PNG base
	"/config/default_emblema.png",
	"/config/default_favicon.png",
	"/config/default_fondo.png",
	"/config/default_logo.png",

	// ⭐ NUEVO: el SW necesita este archivo incluso offline
	"/build/manifest-sw.js",

	// ⭐ NUEVO: el SW necesita este archivo en modo offline
	"/app-shell.html"
];

// Recorrer todas las entradas del manifest
for (const key of Object.keys(manifest)) {
	const entry = manifest[key];

	// JS principal
	if (entry.file) precache.push("/build/" + entry.file);

	// CSS asociados
	if (entry.css) {
		for (const css of entry.css) {
			precache.push("/build/" + css);
		}
	}

	// Archivos importados por otros módulos
	if (entry.imports) {
		for (const imp of entry.imports) {
			if (manifest[imp]?.file) {
				precache.push("/build/" + manifest[imp].file);
			}
		}
	}
}

// Eliminar duplicados
precache = [...new Set(precache)];

const output = `self.__PRECACHE = ${JSON.stringify(precache, null, 2)};`;

// Ojo: ruta relativa al **root del proyecto**
const outputPath = path.resolve(__dirname, "../public/build/manifest-sw.js");
fs.writeFileSync(outputPath, output);

console.log("manifest-sw.js generado correctamente (ESM).");

// ---------------------------- ---------------------------- ---------------------------- ----------------------------
// Generar app-shell.html con el entry real de Vite
// ---------------------------- ---------------------------- ---------------------------- ----------------------------

// Heurística: buscar una entry que tenga "isEntry": true
// (Vite lo pone en el manifest si corresponde)
const entryKey = Object.keys(manifest).find(k => manifest[k].isEntry);

if (!entryKey) {
	throw new Error("No se encontró ninguna entry (isEntry:true) en manifest.json");
}

const entryFile = "/build/" + manifest[entryKey].file;

// Ojo: si tu build separa CSS, NO es necesario linkearlos aquí;
// el JS ya los inyecta/carga según tu setup. Si prefieres, puedes agregarlos.
const appShellHtml = `<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Calidad</title>
</head>
<body>
  <div id="app">
    <p>Sin conexión. Cargando aplicación…</p>
  </div>
  <script type="module" src="${entryFile}"></script>
</body>
</html>
`;

const appShellPath = path.resolve(__dirname, "../public/app-shell.html");
fs.writeFileSync(appShellPath, appShellHtml, "utf8");
console.log("app-shell.html generado correctamente:", entryFile);
