ls *Test.php | sed 's/Test.php/;/' | sed 's/^/php test.php /' | bash
