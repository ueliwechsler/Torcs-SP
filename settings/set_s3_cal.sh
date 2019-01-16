#!/bin/bash

folder="S3_cal";

# Copy quickrace.xml
cp -f  /home/wueli/Torcs-SP/settings/$folder/config/raceman/quickrace.xml /home/wueli/.torcs/config/raceman/quickrace.xml
# Copy human player config
cp -f  /home/wueli/Torcs-SP/settings/$folder/drivers/human/human.xml /home/wueli/.torcs/drivers/human/human.xml
# Copy steering config
cp -f  /home/wueli/Torcs-SP/settings/$folder/drivers/human/preferences.xml /home/wueli/.torcs/drivers/human/preferences.xml
# Screen Setup
cp -f /home/wueli/Torcs-SP/settings/$folder/config/screen.xml /home/wueli/.torcs/config/screen.xml
