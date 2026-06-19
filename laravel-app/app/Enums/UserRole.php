<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Hod = 'hod';
    case Student = 'student';
}
