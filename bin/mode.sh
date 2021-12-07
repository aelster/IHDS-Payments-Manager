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
rm -f -- ../scripts/commonv2.js

if [ "$ans" = "p" ]; then
    ln -v -s /usr/local/Common-societies/css/Common.css ../css/Common.css
    ln -v -s /usr/local/Common-societies/scripts/commonv2.js ../scripts/commonv2.js

else
    ln -v -s /usr/local/Common/css/Common.css ../css/Common.css
    ln -v -s /usr/local/Common/scripts/commonv2.js ../scripts/commonv2.js
fi
