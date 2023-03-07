# HacknFlat

This is an experiment to setup notifications for your search on immoscout24. The script works only partly, so if you look for a good project to setup notifications, please head over to the established python-project [flathunters](https://github.com/flathunters/flathunter/). 

## How it works

The script crawls the json-version of the search result page, that immoscout uses for the maps. The script is blocked initially and periodically after about 20 minutes. To unblock it, you need a fresh cookie from a human driven browser. (Read more in the discussion on [flathunters](https://github.com/flathunters/flathunter/issues/302#issuecomment-1457178967)).

Right now the script shows a simple submit when it is blocked. Simply open another browser window (try incognito mode), open the developer tools, search for a cookie called "reese84", copy the cookie value and paste it into the form:

![Screenshot: paste cookie value manually into the script](/cookievalue.png)

## Goal: Automate the process

The advantage of this script is that (in theory) it does not need a captcha resolving service, because a fresh cookie is enough to unblock the page. 

The question is how you can automate this process. Maybe [imagetyperz](https://www.imagetyperz.com/Forms/api/api.html#-types-of-captcha-palm_tree-task-bypass-any-captchatype) has a solution for this but it is still unclear.

Another aproach might be a browser extension, that automatically stores a cookie from a page to your file system when the page is loaded. You can then combine it with a page refresh extension, store a fresh cookie every minute, and use the cookie value with this script. The closest I found so far is the extension [export cookie for puppeteer](https://github.com/ktty1220/export-cookie-for-puppeteer). If you have any experience with browser extensions, then help is very welcome... 