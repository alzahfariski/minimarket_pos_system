<?php

namespace App\Support\Auth;

enum Role: string
{
    case ADMIN = 'admin';
    case CASHIER = 'cashier';
}
