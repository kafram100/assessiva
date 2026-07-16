<?php

use App\Models\SubAgent;
use App\Models\User;

$dealer = User::where('role', 'dealer')->first();
if (!$dealer) {
    echo "No dealer found to link sub-agent.\n";
    exit;
}

$subPassword = password_hash('TestPass123!', PASSWORD_DEFAULT);

$subAgent = new SubAgent();
$subAgent->agent_id = $dealer->id;
$subAgent->username = 'testsubagent';
$subAgent->email = 'subagent@airlink.com';
$subAgent->password_hash = $subPassword;
$subAgent->balance = 500;
$subAgent->is_active = true;
$subAgent->save();

echo "Sub agent created: subagent@airlink.com\n";
