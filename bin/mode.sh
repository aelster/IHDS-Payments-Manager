#!/bin/bash

ans="x"
done=0
while [ $done -eq 0 ]; do
    case "$ans" in
        d|p)
            done=1;;
        *)
            read -p "Enter mode: (D)eveloper or (P)roduction: " -n 1 ans
            ans=`echo $ans | tr '[:upper:]' '[:lower:]'`
            echo;;
    esac
done

rm -f -- ../css/Common.css
rm -f -- ../scripts/Common.js

if [ "$ans" = "p" ]; then
    ln -v -s /usr/local/site/Common-IHDS-societies/css/Common.css ../css/Common.css
    ln -v -s /usr/local/site/Common-IHDS-societies/scripts/Common.js ../scripts/Common.js

else
    ln -v -s /usr/local/site/Common/css/Common.css ../css/Common.css
    ln -v -s /usr/local/site/Common/scripts/Common.js ../scripts/Common.js
fi
