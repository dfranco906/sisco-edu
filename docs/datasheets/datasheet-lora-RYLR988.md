## RYLR 998

**UART Interface**

**868/915 MHz LoRa®**

**Antenna Transceiver Module**

**Datasheet**

# 9 - FEB- 2026 56312 E 30


```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**PRODUCT DESCRIPTION**

```
The RYLR9 98 transceiver module feature the LoRa® long range modem that provides ultra-long range
spread spectrum communication and high interference immunity whilst minimizing current
consumption.
```
**FEATURES**

- NUVOTON MCU & Semtech LoRa® Engine
- Excellent blocking immunity
- Smart receiving power saving mode
- High sensitivity
- Control easily by AT commands

```
*If you need a special transparent firmware version, please contact us.
```
- Built-in antenna
- Command support data encryption

**APPLICTIONS**

- IoT Applications
- Mobile Equipment
- Home Security
- Industrial Monitoring and Control Equipment

**CERTIFICATION**

- FCC (USA)
- CE RED (EU)
- NCC (Taiwan)
- IC (Canada)


```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**PIN DESCRIPTION**

```
REY AXRY LR
```
```
1 2 3 4 5
```
```
Pin Name I/O Condition
1 VDD I Power Supply
```
2 NRST I (^) RESET(Active Low) 100KΩ Internal pull up,
Pull down at least 100ms
3 RXD I UART Data Input
4 TXD O UART Data Output
5 GND - Ground^
RYLR998_M4 Connector slot that can use I-PEX®^
MHF4® connector to connect with
External Antenna
RYLR


```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**TIMING DIAGRAM**

VDD

RST

TXD

RXD

POWER ON TX/RX POWER OFF

**BLOCK DIAGRAM**

Filter

NUVOTON MCU

#### NRST

#### RXD

#### TXD

Crystal

Processor

#### GPIO

#### TXD

#### RXD

```
Semtech LoRa®
Engine
```
Switch

```
Keep Low before power on
```
```
Keep Low before power on
```
```
Keep Low before power off
```
```
Keep Low before power off
```

```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**SPECIFICATION**

```
Item Min. Typical Max. Unit Condition
```
```
VDD Power supply 2.3 3.3 3.6 V VDD
RF Output power range 0 22 dBm RF Output Power must be set to
less than AT+CRFOP=1 4 to
comply with CE certification.
Filter insertion loss 1 2 3 dB
```
```
RF Sensitivity - 129 dBm
RF Input level 10 dBm
```
```
Frequency range 820 868/915 960 MHz
Frequency accuracy ± 10 ppm
```
```
Transmit Mode current 140 mA RFOP = +22dBm
Receive Mode current 17.5 mA @VDD=+3.3V
```
```
Sleep mode current 10 uA AT+MODE=
@VDD=+3.3V
Smart receiving power saving
mode average current
```
0.0 (^6) 2. 65
5.5 (^) mA 2. 65 mA @AT+MODE=2, 1 000, 1000
If you need lower current
consumption, you can adjust the
AT+MODE=2 time duty.
Baud rate 300 115200 115200 bps 8 , N, 1
Digital Input Level High 0. 8 *VDD VDD V VIH
Digital Input Level Low 0 0. 1 V VIL
Digital Output Level High 0. 8 *VDD VDD V VOH
Digital Output Level Low 0.1^ V VOL^
Cycling (erase / write)
Flash data memory
200 K Cycles^
Weight 1.83 g
Operating temperature - 40 25 +85 ̊C


## 868/915MHz LoRa® Antenna Transceiver Module

Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**TRANSMIT POWER CONSUMPTION TEST**

### 1 59.

### 8 85.

## 10 94.

## 14 115.

## 15 119.

## 17 125.

## 19 133.

   - 0 56. AT+CRFOP (dBm) Typical Current (mA) VDD=3.3V
   - 1 59.
   - 2 63.
   - 3 65.
   - 4 70.
   - 5 73.
   - 6 77.
   - 7 81.
   - 8 85.
   - 9 90.
- 10 94.
- 11 99.
- 12 104.
- 13 110.
- 14 115.
- 15 119.
- 16 122.
- 17 125.
- 18 129.
- 19 133.
- 20 138.
- 21 141.
- 22 144.


```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**BAND PASS FILTER TEST**

Condition :

1. Center frequency : 923MHz
2. SF9, Bandwidth 125KHz, Coding Rate 4/5, Programmed Preamble 12.

```
*Avoid using center frequencies that are 4MHz apart as this may cause crosstalk issues due to IF
demodulation.
```
-
-
-
-
    -

```
0
```
```
50
```
```
922.88 922.9 922.92 922.94 922.96 922.98 923 923.02 923.04 923.06 923.08 923.
```
```
Attenuation
```
```
(dB)
```
```
Frequency(MHz)
```

```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**SMART RECEIVING POWER SAVING MODE**

```
When UART RXD interface receives AT+MODE=2,<RX time>,<Low speed time> command, RF
Reception will enter the <RX time>/<Low speed time> according to the parameters, When RF
Transmission is at <RX time> the data will be decoded. Then the data will be output on UART TXD
interface, The RYLRx98 will return to +MODE=0 at same time.
```
```
If you want to enter “Smart receiving power saving mode” again, you need to execute
AT+MODE=2,<RX time>,<Low speed time> command.
```
```
UART RXD
```
```
RF Reception
```
```
RF Transmission
```
```
UART TXD
```
```
Low speed time Low speed time Low speed time^
```
```
+MODE=0 RX time^
```
```
AT+MODE=2,<RX time>,<Low speed time>
```
```
RX time
```
```
+RCV Show the received
data actively.
```
```
TX time
```
```
Low speed time
```
```
AT+MODE=2,<RX time>,<Low speed time>
```

```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**SMART RECEIVING POWER SAVING MODE CURRENT**

**TEST**

```
Time duty Average current(mA) Parameter
```
```
AT+MODE=2,30,60000 0.0 57 AT+PARAMETER=5,7,1,12 only
AT+MODE=2,100,60000 0.061 Any
```
```
AT+MODE=2,100,50000 0.063 Any
AT+MODE=2,100,40000 0.065 Any
```
```
AT+MODE=2,100,30000 0.067 Any
AT+MODE=2,100,20000 0.069 Any
```
```
AT+MODE=2,100,10000 0.071 Any
AT+MODE=2,100,1000 0.525 Any
```
```
AT+MODE=2,1000,1000 2.65 Any
```

```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**DATA THROUGHPUT TEST**

**Transmitter Settings**

AT+BAND= 915000000

AT+PARAMETER=5,9,1,

```
AT+SEND=6,200,
67890123456789012345678901234567890123456789012345678901234567890123456789012345
678901234567890123456789012345678901234567890123456789
```
**Receiver Settings**

AT+BAND= 915000000

AT+PARAMETER=5,9,1,

AT+ADDRESS=

**Test result**

Each test will send 100 packets, each packet size is 200 BYTES.

PARAMETER Time interval (Seconds) Throughput (BYTES/sec)

5,9,1,4 4.505 4400

7,7,1,24 36.349 550

9,7,1,12<default> 105.155 190

9,8,1,24 55.438 361

9,9,1,24 28.506 702

10,9,1,24 95.218 210

11,9,1,24 109.785 182


```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**DATA LATENCY TIME TEST**

```
Measure the time difference between the UART RXD receiving the AT+SEND command and the
UART TXD outputting +RCV data.
```
```
Parameter Latency time(mSec)
AT+PARAMETER=5,9,1,4 9.
```
```
AT+PARAMETER=5,8,1,12 13.
AT+PARAMETER= 9 , 8 ,1,12 98.
```
```
AT+PARAMETER= 9 ,7,1,12 190.
AT+PARAMETER=11,9,1,24 220.
```
```
UART TXD output +RCV= 1 , 8 , ABCDEFGH, -99, 40
```
```
RF Transmission
```
```
Time difference / Latency time
AT+SEND=0,8,ABCDEFGH to UART RXD
```

```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**APPLICATION SCHEMATIC**

```
RSTRXDTXD
```
```
M
RY LR498 / RY LR
```
```
1 2 3 4 5
```
```
+3.3V
```
```
C
0.1uF
C
10uF
C
47pF
```
```
B
Murata BLM15EG121SN1D
```
```
C
10uF
B
Murata BLM15EG121SN1D
```

```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**DIMENSIONS**


```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

```
Unit : mm
```
***For more detail, please refer to the 3 D model Information.**


```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**TRAY**

Every 100pcs RYLR998 will be placed in a tray. Another tray will be placed above each tray.

```
Unit : mm
```

```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**CERTIFICATION INFORMATION**

```
FCC Statement:
This device complies with part 15 of the FCC Rules. Operation is subject to the following two conditions:
(1) This device may not cause harmful interference, and
(2) this device must accept any interference received, including interference that may cause undesired operation.
```
```
NOTE: This equipment has been tested and found to comply with the limits for a Class B digital device, pursuant to part 15 of the FCC Rules. These
limits are designed to provide reasonable protection against harmful interference in a residential installation.
This equipment generates, uses and can radiate radio frequency energy and, if not installed and used in accordance with the instructions, may cause
harmful interference to radio communications. However, there is no guarantee that interference will not occur in a particular installation.
If this equipment does cause harmful interference to radio or television reception, which can be determined by turning the equipment off and on, the
user is encouraged to try to correct the interference by one or more of the following measures:
—Reorient or relocate the receiving antenna.
—Increase the separation between the equipment and receiver.
—Connect the equipment into an outlet on a circuit different from that to which the receiver is connected.
—Consult the dealer or an experienced radio/TV technician for help.
Changes or modifications not expressly approved by the party responsible for compliance could void the user’s authority to operate the equipment.
```
```
LABEL OF THE END PRODUCT:
The final end product must be labeled in a visible area with the following " Contains TX FCC ID : QLYRYLR 998 ". If the size of the end product is larger
than 8x10cm, then the following FCC part 15.19 statement has to also be available on the label: This device complies with Part 15 of FCC rules.
Operation is subject to the following two conditions: (1) this device may not cause harmful interference
and (2) this device must accept any interference received, including interference that may cause undesired operation.
```
```
ETSI EN 300 220-1 V3.1.1 (2017-02)
ETSI EN 300 220-2 V3.2.1 (2018-06)
```
```
Taiwan NCC Statement 低功率電波輻射性電機管理辦法 :
```
```
第十二條 經型式認證合格之低功率射頻電機，非經許可，公司、商號或使用者均不得擅自變更頻率、加大功率或變更原設
計之特性及功能。
```
```
第十四條 低功率射頻電機之使用不得影響飛航安全及干擾合法通信；經發現有干擾現象時，應立即停用，並改善至無干擾
時方得繼續使用。前項合法通信，指依電信法規定作業之無線電通信。低功率射頻電機須忍受合法通信或工業、科學及醫療
用電波輻射性電機設備之干擾。
```
- **IC Canada compliance**

**QLYRYLR**

**CCAQ22Y10060T**

**31501 - RYLR**


```
868/915MHz LoRa® Antenna Transceiver Module
```
Copyright © 2 021 , REYAX TECHNOLOGY CO., LTD.

**ORDER INFORMATION**

```
Ordering No. Pin Header Antenna or I-PEX® MHF4® Connector
RYLR998 90 Degree Angle Antenna
```
RYLR998_M4 90 Degree Angle (^) I-PEX **®** MHF4 **®** Connector
RYLR998_NP Not mount Antenna
RYLR998_M4_NP Not mount (^) I-PEX **®** MHF4 **®** Connector
**E-mail :** sales@reyax.com
**Website :** [http://reyax.com](http://reyax.com)


