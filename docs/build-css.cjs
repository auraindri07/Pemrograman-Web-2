// Build-only helper. The PHP application serves the generated CSS directly.
const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname,'..');
process.chdir(root);
const postcss = require('../.qa-tools/node_modules/postcss');
const tailwind = require('../.qa-tools/node_modules/tailwindcss');
postcss([tailwind(require('../tailwind.config.cjs'))])
 .process(fs.readFileSync('assets/css/tailwind-input.css','utf8'),{from:'assets/css/tailwind-input.css',to:'assets/css/tailwind.css'})
 .then(result=>{fs.writeFileSync('assets/css/tailwind.css',result.css);console.log('Tailwind CSS built locally.');})
 .catch(error=>{console.error(error);process.exit(1);});
