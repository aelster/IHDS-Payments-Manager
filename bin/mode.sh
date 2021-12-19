#!/bin/bash

if [ "$USER" = "andy" ]; then
    path="/usr/local/site"
else
    path="/home1/joylearn/site"
fi

mode="x"
done=0
while [ $done -eq 0 ]; do
    case "$mode" in
        d|p)
            done=1;;
        *)
            read -p "Enter mode: (D)eveloper or (P)roduction: " -n 1 mode
            mode=`echo $mode | tr '[:upper:]' '[:lower:]'`
            echo;;
    esac
done

rm -f -- ../css/Common.css
rm -f -- ../scripts/Common.js

if [ "$mode" = "p" ]; then
    ln -v -s $path/Common-IHDS-Societies/css/Common.css ../css/Common.css
    ln -v -s $path/Common-IHDS-Societies/scripts/Common.js ../scripts/Common.js

else
    ln -v -s $path/Common/css/Common.css ../css/Common.css
    ln -v -s $path/Common/scripts/Common.js ../scripts/Common.js
fi
