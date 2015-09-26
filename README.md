# Deux Titres

A french version of [TwoHeadlines](https://github.com/dariusk/twoheadlines) by @dariusk in PHP
A Twitter bot that looks at french news headlines and confuses them : [@DeuxTitres](https://twitter.com/DeuxTitres)

## Documentation

See @dariusk's [nice-looking explanation of his index.js](http://tinysubversions.com/twoheadlines/docs/) in order to understand how the bot works.
The PHP code basically does the same using the PHP features instead of Javascript.

## Install

- Clone the repository
- [Create a Twitter app](https://apps.twitter.com/) with your bot's account
- Set access tokens in the `DeuxTitres.php` file in the tweet() function
- Create a CRON Job that runs `DeuxTitres.php` whenever you want your bot to tweet

## License
The source code of this bot is available under the terms of the [MIT license](http://www.opensource.org/licenses/mit-license.php).
