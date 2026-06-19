import re

path = 'app/Http/Controllers/Lecturer/LecturerQuestionBankController.php'
with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace('createDraftQuestion', 'createQuestion')
content = re.sub(r"->whereIn\('status', \['draft', 'rejected'\]\)", "->where('is_active', true)", content)
content = re.sub(r"->where\('status', '!=', 'official'\)", "->where('is_active', true)", content)
content = re.sub(r"->where\('status', 'draft'\)", "->where('is_active', true)", content)
content = re.sub(r"->whereNotIn\('status', \['archived'\]\)", "->where('is_active', true)", content)
content = re.sub(r"->orWhere\('status', 'official'\)", "->orWhere('is_active', true)", content)
content = re.sub(r"\$question->status !== 'official'", "!\$question->is_active", content)
content = re.sub(r"'status' => 'draft',\s*'visibility' => 'personal',", "'is_active' => true,", content)

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
