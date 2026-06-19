const fs = require('fs');
const path = require('path');
const walkSync = (dir, callback) => {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const filepath = path.join(dir, file);
        if (fs.statSync(filepath).isDirectory()) {
            walkSync(filepath, callback);
        } else {
            callback(filepath);
        }
    }
};

walkSync('app/Http/Controllers/Lecturer', (filepath) => {
    if (filepath.endsWith('.php')) {
        let content = fs.readFileSync(filepath, 'utf8');
        content = content.replace(/Inertia::render\('Lecturer\//g, "Inertia::render('Management/");
        fs.writeFileSync(filepath, content, 'utf8');
    }
});
console.log('Controllers updated.');
