# HacknFlat

(!!! this is a private experiment, so use on your own risk !!!)

This is a simple crawler that checks your search on immobilienscout24.de every minute and sends an e-mail-alert, if a new result has been found. It tries to fix the missing or slow alert features from immobilienscout. If you are looking for a more established project, please head over to [flathunters](https://github.com/flathunters/flathunter/). Unlike flathunters, this script does not need a captcha solver or a telegram account. But it is also a bit tricky to setup for novices. 

## Preconditions

I developed this script on **windows 11** and **xampp**. I did not test it on my linux machine, so I don't know, if it works there or in any other environment. I use **firefox** as my standard browser and **chrome** only for the cookie-refresh-feature (see below). I will **NOT fix** any bugs or answer to any questions/issues because the script works for me and I spend my time on [other projects](https://github.com/typemill/typemill/).

## How to use

### Install

* clone this repository or download the zip. 
* Switch to the folder of the cloned version and run composer update.

### Configure immoalert.php

* Go to immobilienscout24 and configure your search.
* !!! Do not use customized search areas, this script only runs with simple radius search !!!!
* Copy the search url.
* Open the file "immoalert.php" and search for the $config-array on top. 
* "immoWebUrl" => paste your search url here. 
* "alert_mails" => Enter one or more email-addresses separated by comma.

### Configure emails in xampp

If you want to send mails from xampp you have to configure it. Just [search for it](https://duckduckgo.com/?t=ffab&q=xampp+send+mail+from+localhost&ia=web).

### Refresh cookie

This script only works, if it gets a fresh cookie called "reese84" from immobilienscout24.de. The cookie contains a token that ensures, that you are a human and not a bot. Thus, this cookie must be generated with a human driven browser. Cookies generated with auttomatic browsers like chrome headless or selenium will fail.

### Manual solution

If you do not like the automatic solution described below, then you can open the website immobiilienscout24.de, copy the value of the cookie "reese84" manually and paste it into the input form, that the shows up if the script immoalert.php fails.

![Screenshot: paste cookie value manually into the script](/cookievalue.png)

### Automattic solution

To automate the process, you will need two chrome extensions:

* An extension that automatically downloads the cookie "reese84" to this repository so the script can use it. This chrome-extension is included in this repository in the folder "extension". It might have bugs and again: use on your own risk!!!
* An extension that refreshes a tab automatically after a certain period.

The (simple) steps in detail:

* Open your chrome browser. 
* Add the folder "extension" from this repository as an [unpacked extension to chrome](https://developer.chrome.com/docs/extensions/mv3/getstarted/development-basics/#load-unpacked). The extension will automatically download the cookie "reese84" from immobilienscout.de after a page load.
* Install a [page refresh extension](https://duckduckgo.com/?q=chrome+page+refresh+extension&t=ffab&ia=web) of your choice.
* Change the default [download location of chrome](https://support.google.com/chrome/answer/95759?hl=en&co=GENIE.Platform=Desktop#zippy=%2Cchange-download-locations) to the base folder of this repository, so that the script can read the cookie.
* Go to the json-url of your immosearch-url. You can find the json-version by replacing /Suche/' with '/Suche/controller/mapResults.go?searchUrl=/Suche/' in your url.
* Activate the page refresh extension for this page and configure it, so that the page is reloaded every 60 seconds.
* Check in your repository folder, if text files like "reese84.txt" are generated after a page refresh.

### Run the script

If you are done, simply open the file "immoalert.php" in your browser, e.g. "https://localhost/HacknFlat/immoalert.php". I use firefox for this, chrome only does the cookie download in my setup.

Good luck for your search!