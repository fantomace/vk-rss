## Description ##
Convert vk.com/feed to RSS 2.0 format

## Features ##
  * last 90 wall events
  * include video, audio, images, documents
  * dates
  * links to source events
  * clickable objects

## Demo ##
[demo](http://kadukmm.org.ua/~kadukmm/rss/vk2rss.php)

## Russian ##
```
Стояла Задача:
Посты со стены в ВКонтакте импортировать на сайт.

На входе
- id пользователя
на выходе
- RSS 2.0

Результат можно импортировать в Drupal, Juumla, Google Reader, WordPress и пр.
Был задуман для решения проблемы кросспостинга.

Т.е. запись публикуется в ВКонтакте (включая видео), 
оттуда отправляется в Facebook, Tweeter, LiveJournal и на сайт.
Также появилась возможность подписываться в Google Reader.
```

## Usage ##
```
php script.php <owner_id>
```
Где owner\_id - код пользователя ВКонтакте.

## Authors ##
Работу выполнил: http://www.free-lance.ru/users/kadukmm/

Проспонсировал: http://www.free-lance.ru/users/vital_fadeev