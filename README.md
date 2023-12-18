1. git clone https://github.com/DVKuklin/dailygrow_test_task_back.git .
2. composer install
3. php artisan key:generate
4. cp .env.example .env
5. Указать данные для доступа к бд в .env
6. php artisan migrate
7. Создать пользователя php artisan app:create-user --login='den' --password='1111' --b24link='https://b24-l0kils.bitrix24.ru/rest/1/sdfsdfsdfsdf/'
8. Для локальной разработки php artisan serve
