<?php
/**
 * Demo accounts created by app/Modules/User/seed.sql (local test values, never real people).
 * tests/check.php signs in with them to open the pages of each role. Every module may use these ids in its seed.sql.
 * Password of every account: Skydrop2026.
 */
return [
    'admin'      => ['id' => 1, 'email' => 'admin@skydrop.test',      'password' => 'Skydrop2026'],
    'shop_owner' => ['id' => 2, 'email' => 'owner@skydrop.test',      'password' => 'Skydrop2026'],
    'customer'   => ['id' => 3, 'email' => 'customer@skydrop.test',   'password' => 'Skydrop2026'],
    'advertiser' => ['id' => 4, 'email' => 'advertiser@skydrop.test', 'password' => 'Skydrop2026'],
    // id 5: second shop owner (owns shop 2), id 6: deactivated customer (login refused),
    // ids 7 to 13: owners of the demo shops 3 to 9 (owner7@skydrop.test ... owner13@skydrop.test).
];
