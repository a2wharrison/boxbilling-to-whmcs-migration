# boxbilling-to-whmcs-migration
This script will migrate all clients and invoices from BoxBilling to WHMCS using both software APIs.

Put project files in one folder, edit **retrieve_boxbilling.php** with required information. 
And then run **retrieve_boxbilling.php** file.

It's recommended that you set **max_execution_time** to unlimited and **memory_limit** to at least 128MB.
Also, once you start **retrieve_boxbilling.php** via browser don't stop it or reload it until it's finished.

If script fails or is interrupted you will need to have CLEAN WHMCS installation again, So please, before starting this script, make backup of WHMCS database which you can re-import to try again.

In order for WHMCS API connection to work, you will need to whitelist and allow IP of the server from which you are running this script in WHMCS settings.
