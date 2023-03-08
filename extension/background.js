chrome.webNavigation.onCompleted.addListener((details) => {
  chrome.tabs.query({
    currentWindow: true,
    active: true
  }, (tabs) => {

    if (tabs.length === 0) {
      return;
    }

    const target = tabs[0];

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

      let jsondata = JSON.stringify(cookies, null, 2);
      let url      = "data:text/plain," + encodeURIComponent(jsondata);
      let filename = "reese84.txt";
      chrome.downloads.download({
        url      : url,
        filename : filename,
        saveAs   : false,
        conflictAction: 'overwrite'
      });
    });
  });
});

/*
// https://github.com/GoogleChrome/chrome-extensions-samples/tree/main/_archive/mv2/api/downloads
// Force all downloads to overwrite any existing files instead of inserting
// ' (1)', ' (2)', etc.
chrome.downloads.onDeterminingFilename.addListener(function(item, suggest) {
  suggest({filename: item.filename,
           conflict_action: 'overwrite',
           conflictAction: 'overwrite'});
  // conflict_action was renamed to conflictAction in
  // https://chromium.googlesource.com/chromium/src/+/f1d784d6938b8fe8e0d257e41b26341992c2552c
  // which was first picked up in branch 1580.
});
*/