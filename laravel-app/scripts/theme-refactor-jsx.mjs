/**
 * Centralized replacement of hardcoded dark Tailwind classes with theme tokens.
 * Run from laravel-app: node scripts/theme-refactor-jsx.mjs
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.join(__dirname, '..');

const dirs = [
    path.join(root, 'resources/js/Pages'),
    path.join(root, 'resources/js/Components'),
    path.join(root, 'resources/js/Layouts'),
];

/** Longer / more specific patterns first */
const literalReplacements = [
    [/bg-\[#0d1117\]/g, 'bg-theme-elevated'],
    [/bg-\[#071120\]/g, 'bg-theme-surface-soft'],
    [/border-white\/\[0\.08\]/g, 'border-theme-line'],
    [/border-white\/\[0\.06\]/g, 'border-theme-line'],
    [/border-white\/\[0\.1\]/g, 'border-theme-line'],
    [/border-white\/10/g, 'border-theme-line'],
    [/border-white\/15/g, 'border-theme-line'],
    [/border-white\/20/g, 'border-theme-line'],
    [/border-white\/25/g, 'border-theme-line'],
    [/border-slate-700\/80/g, 'border-theme-line'],
    [/border-slate-700/g, 'border-theme-line'],
    [/border-slate-600/g, 'border-theme-line'],
    [/border-slate-800/g, 'border-theme-line'],
    [/bg-slate-950\/60/g, 'bg-theme-glass'],
    [/bg-slate-950\/40/g, 'bg-theme-glass'],
    [/bg-slate-950/g, 'bg-theme-mesh'],
    [/bg-slate-900\/90/g, 'bg-theme-elevated'],
    [/bg-slate-900\/80/g, 'bg-theme-elevated'],
    [/bg-slate-900\/70/g, 'bg-theme-elevated'],
    [/bg-slate-900\/60/g, 'bg-theme-elevated'],
    [/bg-slate-900\/50/g, 'bg-theme-glass'],
    [/bg-slate-900\/40/g, 'bg-theme-glass'],
    [/bg-slate-900\/30/g, 'bg-theme-glass'],
    [/bg-slate-900/g, 'bg-theme-elevated'],
    [/bg-slate-800\/80/g, 'bg-theme-surface-soft'],
    [/bg-slate-800/g, 'bg-theme-surface-soft'],
    [/bg-slate-700/g, 'bg-theme-highlight'],
    [/bg-black\/30/g, 'bg-theme-highlight'],
    [/bg-black\/25/g, 'bg-theme-highlight'],
    [/bg-black\/20/g, 'bg-theme-highlight'],
    [/bg-black\/40/g, 'bg-theme-highlight'],
    [/bg-zinc-900/g, 'bg-theme-elevated'],
    [/bg-gray-900/g, 'bg-theme-elevated'],
    [/bg-gray-800/g, 'bg-theme-surface-soft'],
    [/text-slate-100/g, 'text-theme-strong'],
    [/text-slate-200/g, 'text-theme-muted'],
    [/text-slate-300/g, 'text-theme-muted'],
    [/text-slate-400/g, 'text-theme-dim'],
    [/text-slate-500/g, 'text-theme-dim'],
    [/ring-white\/5/g, 'ring-theme-line'],
    [/ring-white\/10/g, 'ring-theme-line'],
    [/shadow-inner shadow-black\/40/g, 'shadow-inner'],
    [/shadow-black\/20/g, 'shadow-theme-card'],
];

function shouldKeepTextWhite(line) {
    return (
        /bg-sky-|bg-emerald-|bg-red-|bg-indigo-|bg-violet-|bg-fuchsia-|bg-gradient|from-ai-|to-ai-|btn-ai-primary|bg-green-|bg-amber-|bg-orange-/.test(
            line,
        )
    );
}

function refactorContent(src) {
    let out = src;
    for (const [re, rep] of literalReplacements) {
        out = out.replace(re, rep);
    }
    out = out.replace(/bg-slate-800\/50/g, 'bg-theme-highlight');

    const lines = out.split('\n');
    const next = lines.map((line) => {
        if (!line.includes('text-white')) {
            return line;
        }
        if (shouldKeepTextWhite(line)) {
            return line;
        }
        return line.replace(/\btext-white\b/g, 'text-theme-strong');
    });
    return next.join('\n');
}

function walk(dir, acc) {
    if (!fs.existsSync(dir)) {
        return acc;
    }
    for (const name of fs.readdirSync(dir)) {
        const p = path.join(dir, name);
        const st = fs.statSync(p);
        if (st.isDirectory()) {
            walk(p, acc);
        } else if (name.endsWith('.jsx')) {
            acc.push(p);
        }
    }
    return acc;
}

const files = dirs.flatMap((d) => walk(d, []));
let changed = 0;
for (const file of files) {
    const before = fs.readFileSync(file, 'utf8');
    const after = refactorContent(before);
    if (after !== before) {
        fs.writeFileSync(file, after, 'utf8');
        changed++;
        console.log('updated', path.relative(root, file));
    }
}
console.log('files changed:', changed, 'of', files.length);
