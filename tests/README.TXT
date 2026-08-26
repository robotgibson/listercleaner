Running the Suite End-to-End

1. Initialize the local runtime environment (creates database tables and downloads WP framework assets to your temporary path):
bash

bash bin/install-wp-tests.sh my_lister_test_db db_user db_password localhost latest

Execute your test suite wrapper directly via PHPUnit:

bash

phpunit
