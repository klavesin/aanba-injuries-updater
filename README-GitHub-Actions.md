# GitHub Actions updater for AANBA injuries

## Что делает этот репозиторий
- запускает `scripts/update_injuries.php` по расписанию в GitHub Actions
- собирает свежий `data/injuries.json`
- загружает его на ваш сайт на SpaceWeb
- опционально загружает `logs/update_injuries.log`

## Что загрузить в GitHub
В репозиторий нужны как минимум:
- `.github/workflows/update-injuries.yml`
- `scripts/update_injuries.php`
- `data/injuries.json`
- `logs/update_injuries.log`

## Какие секреты добавить в GitHub
Repository -> Settings -> Secrets and variables -> Actions

Создайте 3 секрета:

### `UPLOAD_BASE_URL`
Полный базовый URL до папки сайта.

Примеры:
- `ftp://YOUR_FTP_HOST/public_html`
- `ftps://YOUR_FTP_HOST/public_html`

### `UPLOAD_USERNAME`
FTP/FTPS логин от SpaceWeb.

### `UPLOAD_PASSWORD`
FTP/FTPS пароль от SpaceWeb.

## Как запускать вручную
GitHub -> Actions -> `Update NBA injuries JSON` -> `Run workflow`

## Как это работает сейчас
Updater больше не зависит от HTML-страницы `official.nba.com`.
Он ищет свежий официальный PDF напрямую по шаблону `Injury-Report_YYYY-MM-DD_h_mmAM.pdf` на `ak-static.cms.nba.com`.

## После первого успешного запуска
Проверьте:
- `https://aanba.info/data/injuries.json`
- `https://aanba.info/`
- `https://aanba.info/logs/update_injuries.log`
