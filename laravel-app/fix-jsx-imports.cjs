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

walkSync('resources/js/Pages/Management', (filepath) => {
    if (filepath.endsWith('.jsx')) {
        let content = fs.readFileSync(filepath, 'utf8');
        content = content.replace(/@\/Pages\/Lecturer\//g, "@/Pages/Management/");
        content = content.replace(/\.\.\/Lecturer\//g, "../Management/");
        fs.writeFileSync(filepath, content, 'utf8');
    }
});
console.log('JSX internal imports updated.');
