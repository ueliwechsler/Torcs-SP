#!/bin/bash

# Copy quickrace.xml
cp -f  /home/ueli/.torcs/savedConfig_S3/config/raceman/quickrace.xml /home/ueli/.torcs/config/raceman/quickrace.xml
# Copy human player config
cp -f  /home/ueli/.torcs/savedConfig_S3/drivers/human/human.xml /home/ueli/.torcs/drivers/human/human.xml
# Copy steering config
cp -f /home/ueli/.torcs/savedConfig_S3/drivers/human/preferences.xml /home/ueli/.torcs/drivers/human/preferences.xml
# Screen Setup
cp -f /home/ueli/.torcs/savedConfig_S3/config/screen.xml /home/ueli/.torcs/config/screen.xml