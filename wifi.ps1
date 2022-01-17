set-ExecutionPolicy RemoteSigned
@echo off
powershell -c "Get-Content -LiteralPath '%~0' | Select-Object -Skip 3 | Out-String | Invoke-Expression" 
pause&exit
$wifiName="MERCURY_249A"; # WIFI NAME
$wifiKey="18280484479"; # WIFI PASSWORD
$xml_Template=@"
<?xml version="1.0"?>
<WLANProfile xmlns="http://www.microsoft.com/networking/WLAN/profile/v1">
	<name>WIFI_NAME</name>
	<SSIDConfig>
		<SSID>
			<hex>WIFI_NAME_HEX</hex>
			<name>WIFI_NAME</name>
		</SSID>
	</SSIDConfig>
	<connectionType>ESS</connectionType>
	<connectionMode>manual</connectionMode>
	<MSM>
		<security>
			<authEncryption>
				<authentication>WPA2PSK</authentication>
				<encryption>AES</encryption>
				<useOneX>false</useOneX>
			</authEncryption>
			<sharedKey>
				<keyType>passPhrase</keyType>
				<protected>false</protected>
				<keyMaterial>WIFI_KEY</keyMaterial>
			</sharedKey>
		</security>
	</MSM>
	<MacRandomization xmlns="http://www.microsoft.com/networking/WLAN/profile/v3">
		<enableRandomization>false</enableRandomization>
		<randomizationSeed>634562794</randomizationSeed>
	</MacRandomization>
</WLANProfile>
"@
$wifiNameHex="";
foreach ($each in [System.Text.Encoding]::UTF8.GetBytes($wifiName)) { $wifiNameHex+=("{0:x}" -f $each).ToUpper();}
$xmlFile="WLAN-{0}.xml" -f $wifiName
$xml=$xml_Template -replace "WIFI_NAME_HEX",$wifiNameHex -replace "WIFI_NAME",$wifiName -replace "WIFI_KEY",$wifiKey
$xml | Out-File $xmlFile -Encoding utf8 
netsh wlan delete profile $wifiName 2>$null
netsh wlan add profile $xmlFile 
netsh wlan connect $wifiName
Remove-Item -LiteralPath $xmlFile
