chrome.webNavigation.onCompleted.addListener((details) => {
  chrome.tabs.query({
    currentWindow: true,
    active: true
  }, (tabs) => {

    if (tabs.length === 0) {
      return;
    }

    const target = tabs[0];

    if(target.url.indexOf("immobilienscout24.de") === -1){
      return false;
    }

    const param = {
      name: "reese84"
    };

    if (target.cookieStoreId) {
      param.storeId = target.cookieStoreId;
    }

    chrome.cookies.getAll(param, (cookies) => {
      cookies: cookies.map((c) => {
          const result = {
            name: c.name,
            value: c.value,
            domain: c.domain,
            path: c.path,
            expires: c.expirationDate || -1,
            httpOnly: c.httpOnly,
            secure: c.secure
          };
          if (['lax', 'strict'].includes(c.sameSite)) {
            result.sameSite = c.sameSite.replace(/^./, (p) => p.toUpperCase());
          }
          return result;
      });
      if(cookies.length > 0)
      {
        let jsondata = JSON.stringify(cookies, null, 2);
        let url      = "data:text/plain," + encodeURIComponent(jsondata);
        let filename = "reese84.txt";
        chrome.downloads.download({
          url      : url,
          filename : filename,
          saveAs   : false
//          conflictAction: 'overwrite'
        });
      }
    });
  });
});