<?php

namespace App\Enums;

enum PracticeVisibility: string
{
    case Public = 'public';
    case DepartmentOnly = 'department_only';
    case ProgramOnly = 'program_only';
}
