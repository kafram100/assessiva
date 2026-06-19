const fs = require('fs');
const path = require('path');

const walkSync = (dir, callback) => {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const filepath = path.join(dir, file);
        const stats = fs.statSync(filepath);
        if (stats.isDirectory()) {
            walkSync(filepath, callback);
        } else if (stats.isFile()) {
            callback(filepath);
        }
    }
};

const pagesDir = 'resources/js/Pages/Lecturer';
walkSync(pagesDir, (filepath) => {
    if (filepath.endsWith('.jsx')) {
        let content = fs.readFileSync(filepath, 'utf8');
        content = content.replace(/import LecturerLayout from '.*';/g, "import AdminLayout from '@/Layouts/AdminLayout';");
        content = content.replace(/<LecturerLayout/g, '<AdminLayout');
        content = content.replace(/<\/LecturerLayout>/g, '</AdminLayout>');
        content = content.replace(/route\('lecturer\./g, "route('admin.management.");
        content = content.replace(/route\(\"lecturer\./g, 'route("admin.management.');
        fs.writeFileSync(filepath, content, 'utf8');
    }
});

// Also update web.php routes
let webPhpPath = 'routes/web.php';
let webPhpContent = fs.readFileSync(webPhpPath, 'utf8');
webPhpContent = webPhpContent.replace(
    /Route::middleware\(\['auth', 'approved_staff', 'lecturer_area'\]\)->prefix\('lecturer'\)->name\('lecturer\.'\)->group/g,
    "Route::middleware(['auth', 'admin'])->prefix('admin/management')->name('admin.management.')->group"
);
fs.writeFileSync(webPhpPath, webPhpContent, 'utf8');

console.log('Finished refactoring Lecturer to Admin');
