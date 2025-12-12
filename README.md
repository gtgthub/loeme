Limit-Order-Exchange-Mini-Engine (Used Laravel12, Vue2.0, Postgres14.x, Tailwind)

Steps to host and run this app:
1. Install Postgres 14.x. Keep db user/pwd as 'postgres'. Create a db 'loeme'.
2. Download Laravel 12 and add this .env.bak as .env file
3. run 'composer install' inside the project.
4. run 'composer require pusher/pusher-php-server'
5. Register few users. No need to verify.
6. It'lll auto asisgn 10K USD and 100 of each asset(BTC, Eth, USDT)
7. You can do trading and test the app.

PS: How to run it:
1. Open terminal, go the root folder. Run 'npm run dev'
2. Open another terminal, go the root folder. Run 'php artisan serve --port=1111'
3. You can now open the app on your browser as : http://localhost:1111/