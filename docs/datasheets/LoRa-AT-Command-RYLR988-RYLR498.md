Copyright © 20 20 , REYAX TECHNOLOGY CO., LTD. (^1)

## APPLY FOR:

#### 1. RYLR 998

#### 2. RYLR

## RYLR998_RYLR498 NETWORK STRUCTURE

```
With the own LoRa® wireless transceiver function and the application program designed by
customers, the RYLR998 and RYLR498 can achieve different network architectures such as "Point to
Point", "Point to Multipoint" or " Multipoint to Multipoint ". The figure below shows that the
modules can communicate with each other only by setting the same NETWORKID. If the ADDRESS of
specified receiver belongs to different group, it is not able to communicate with each other.
```
## LoRa

## ®

## AT COMMAND GUIDE

# 7 - AUG- 2025 56312 E 33


Copyright © 20 21 , REYAX TECHNOLOGY CO., LTD.

## THE SEQUENCE OF USING AT COMMAND

1. Use “ **AT+ADDRESS** ” to set ADDRESS. The ADDRESS is regard as the identification of transmitter or
    specified receiver.
2. Use “ **AT+NETWORKID** ” to set the ID of LoRa® network. This is a Group function. Only by setting
    the same NETWORKID can the modules communicate with each other. If the ADDRESS of
    specified receiver is belong to different group, it is not able to communicate with each other.
3. Use” **AT+BAND** ” to set the center frequency of wireless band. The transmitter and the receiver
    are required to use the same frequency to communicate with each other.
4. Use” **AT+PARAMETER** ” to set the RF wireless parameters. The transmitter and the receiver are
    required to set the same parameters to communicate with each other. The parameters of which
    as follows:
    [1] <Spreading Factor>: The larger the SF is, the better the sensitivity is. But the transmission
    time will take longer.
    [2] <Bandwidth>: The smaller the bandwidth is, the better the sensitivity is. But the
    transmission time will take longer.
    [3] <Coding Rate>: The coding rate will be the fastest if setting it as 1.
    [4] <Programmed Preamble>: Preamble code. If the preamble code is bigger, it will result in the
    less opportunity of losing data. Generally preamble code can be set above 10 if under the
    permission of the transmission time. Recommend to set “ **AT + PARAMETER = 9 ,7,1, 12** ”
    [5] When the Payload length is greater than 100Bytes, recommend to set
       “ **AT + PARAMETER = 8 ,7,1, 12** ”
5. Use “ **AT+SEND** ” to send data to the specified ADDRESS. Please use “LoRa® Modem Calculator
    Tool” to calculate the transmission time. Due to the program used by the module, the payload
    part will increase more 8 bytes than the actual data length.

## UART Interface test tool

```
You can use our tools to test and understand REYAX products more quickly.
[1]UART Bridge tool : RYLS135 can connect the computer to the UART interface of
REYAX products.
https://reyax.com/products/RYLS
```
```
[2]Free software tool : COMFORT is a UART/Comport software that can execute
AT commands on Windows® operating systems.
https://reyax.com//products/COMFORT_Software
```
### ^


Copyright © 20 21 , REYAX TECHNOLOGY CO., LTD.

### AT Command Set

```
It is required to key in “\r\n” or “0x0D 0x0A” in the end of all AT Commands.
Add“? ”in the end of the commands to ask the current setting value.
It is required to wait until the module replies +OK so that you can execute the next AT command.
```
**1. AT** Test if the module can respond to Commands.
**2. Software RESET
3. AT+MODE** Set the wireless work mode.

```
Syntax Response
AT +OK
```
```
Syntax Response
AT+RESET +RESET
+READY
```
```
Syntax Response
AT+MODE=<Parameter>[,<RX time>,<Low speed time>]
<Parameter>range 0 to 2
0 ：Transceiver mode (default).
1 ：Sleep mode.
Example : Set to sleep mode.
AT+MODE= 1
2 : Smart receiving power saving mode
The switch between receiving mode and low speed mode can be
used to achieve the effect of power saving, and the appropriate
transmission time must be adjusted by yourself to match this
mode.
```
```
<RX time>=30ms~60000ms, (default 1000)
<Low speed time>=30ms~60000ms, (default 1000 )
```
```
When the correct LoRa® data format is received, it will return to
the transceiver mode.
When the received data is correct, +RCV format data will be output.
Example : The Smart receiving power saving mode.
AT+MODE=2, 3 000, 3000
Set to turn on receiving mode for 3 seconds and then low speed mode for
3 seconds to cycle until the correct signal is received.
```
#### +OK

```
AT+MODE? ‘When MODE=
AT+MODE? Or Any digital signal ‘When MODE=
AT+MODE? Or Any digital signal ‘When MODE=
```
#### +MODE= 0

#### +MODE= 0

#### +MODE= 0


Copyright © 20 21 , REYAX TECHNOLOGY CO., LTD.

**4. AT+IPR** Set the UART baud rate.
**5. AT+BAND** Set RF Frequency.

```
Syntax Response
```
```
AT+IPR=<rate>
<rate> is the UART baud rate：
300
1200
4800
9600
19200
28800
38400
57600
115200(default)
```
```
Example: Set the baud rate as 9600,
*The settings will be memorized in Flash.
AT+IPR=
```
```
+IPR=<rate>
```
#### AT+IPR? +IPR=

```
Syntax Response
AT+BAND=<Parameter>,<Frequency Memory>
<Parameter>is the RF Frequency, Unit is Hz
49 0000000: 4 9 0000000Hz(default: RYLY 498 )
915000000: 915000000Hz(default: RYLY 998 )
```
```
<Frequency Memory> M for memory
Example: Set the frequency as 868500000Hz.
AT+BAND=
```
```
Example: Set the frequency as 868500000Hz
and be memorized in Flash.
```
#### AT+BAND=868500000,M

#### +OK

#### AT+BAND? +BAND=


Copyright © 20 21 , REYAX TECHNOLOGY CO., LTD.

**6. AT+PARAMETER** Set the RF parameters.
**7. AT+ADDRESS** Set the ADDRESS ID of module LoRa®.

```
Syntax Response
AT+PARAMETER=<Spreading Factor>,
<Bandwidth>,<Coding Rate>,
```
```
<Programmed Preamble>
<Spreading Factor> 5 ~1 1 (default 9 )
*SF 7 to SF9 at 125kHz, SF 7 to SF10 at 250kHz, and SF 7 to SF11 at 500kHz
```
```
<Bandwidth> 7 ~9, list as below：
7: 125 KHz (default)
8: 250 KHz
9: 500 KHz
<Coding Rate>1~4, (default 1)
1 : Coding Rate 4/
2 :Coding Rate 4/ 6
3 :Coding Rate 4/ 7
4 :Coding Rate 4/ 8
```
```
<Programmed Preamble>(default 12 )
When NETWORKID=18, The value can be
configured to 4 ~ 24.
Other NETWORKID can only be configured to 12.
```
```
Example: Set the parameters as below,
<Spreading Factor> 7 , <Bandwidth> 500 KHz, <Coding
Rate> 4, <Programmed Preamble> 15.
AT+PARAMETER=7, 9 ,4, 15
```
#### +OK

#### AT+PARAMETER? +PARAMETER=7, 9 ,4, 15

```
Syntax Response
AT+ADDRESS=<Address>
<Address>=0~65535 (default 0 )
Example: Set the address of module as 120.
*The settings will be memorized in Flash.
AT+ADDRESS=
```
#### +OK

#### AT+ADDRESS? +ADDRESS=


Copyright © 20 21 , REYAX TECHNOLOGY CO., LTD.

**8. AT+NETWORKID** Set the network ID.
**9. AT+CPIN** Set the domain password
    Syntax Response
       AT+CPIN=<Password>,<Memory>
       <Password>An 8 character long password
       From 00000001 to FFFFFFFF,
       Only by using same password can the data be
       recognized.
       After resetting, the previously password will
       disappear.

```
Example：Set the password to EEDCAA
AT+CPIN=EEDCAA
```
```
Example: Set the password to EEDCAA90 and
be memorized in Flash.
```
#### AT+CPIN= EEDCAA90,M

#### +OK

```
AT+CPIN? (default)
AT+CPIN? (After setting the password)
```
```
+CPIN=No Password!
+CPIN=eedcaa
```
```
Syntax Response
```
```
AT+NETWORKID=<Network ID>
<NetworkID>= 3 ~15,18(default18)
Example: Set the network ID as 6,
*The settings will be memorized in Flash.
AT+NETWORKID=
```
#### +OK

#### AT+NETWORKID? +NETWORK=


Copyright © 20 21 , REYAX TECHNOLOGY CO., LTD.

**10. AT+CRFOP** Set the RF output power.
**11. AT+SEND** Send data to the appointed address by Command Mode.

```
Syntax Response
AT+CRFOP=<power>
<power>0~ 22 dBm
22 : 22dBm(default)
21 : 21 dBm
2 0: 20 dBm
......
01 : 1dBm
00 : 0dBm
Example: Set the output power as 10dBm,
AT+CRFOP=
```
```
* RF Output Power must be set to less than
AT+CRFOP=1 4 to comply CE certification.
```
#### +OK

#### AT+CRFOP? +CRFOP=

```
Syntax Response
AT+SEND=<Address>,<Payload Length>,<Data>
<Address>0~65535, When the <Address> is 0,
it will send data to all address (From 0 to
65535.)
<Payload Length> Maximum 2 40 bytes
<Data>ASCII Format
Example : Send HELLO string to the Address 50,
AT+SEND=50,5,HELLO
```
#### +OK

```
Search last transmit data,
AT+SEND?
```
#### +SEND=50,5,HELLO


Copyright © 20 21 , REYAX TECHNOLOGY CO., LTD.

**12. +RCV** Show the received data actively.
**13. AT+UID?** To inquire module ID. 12BYTES
**14. AT+VER?** To inquire the firmware version.
**15. AT+FACTORY** Set all current parameters to manufacturer defaults.

```
Syntax Response
+RCV=<Address>,<Length>,<Data>,<RSSI>,<SNR>
<Address> Transmitter Address ID
<Length> Data Length
<Data> ASCll Format Data
<RSSI> Received Signal Strength Indicator
<SNR> Signal-to-noise ratio
Example: Module received the ID Address 50 send 5 bytes data,
Content is HELLO string, RSSI is -99dBm, SNR is 40, It will show as below.
+RCV=50, 5, HELLO, - 99, 40
```
```
Syntax Response
AT+UID? +UID=
```
```
Syntax Response
AT+VER? +VER=RYLRxx8_Vx.x.x
```
```
Syntax Response
AT+FACTORY
Manufacturer defaults:
BAND：490MHz(RYLR498)/915MHz(RYLR998)
UART： 115200
Spreading Factor： 9
Bandwidth：125kHz
Coding Rate： 1
Preamble Length： 12
Address： 0
Network ID： 18
CRFOP： 22
```
#### +FACTORY


Copyright © 20 21 , REYAX TECHNOLOGY CO., LTD.

**16. Other messages
17. Error result codes
18. AT+FCCT** Set the EMC certification mode.

```
Narrative Response
After RESET +RESET
+READY
```
```
Narrative Response
There is not “enter” or 0x0D 0x0A in the end of the AT
Command.
```
```
+ERR=
```
```
The head of AT command is not “AT” string. +ERR=^
```
```
Unknown command. +ERR=^
```
```
The data to be sent does not match the actual length +ERR=^5
```
```
TX is over times. +ERR=^
```
```
CRC error. +ERR=^
```
```
TX data exceeds 240bytes. +ERR=^
```
```
Failed to write flash memory. +ERR=1^4
```
```
Unknown failure. +ERR=15 Try using AT+NOIDEA
```
```
Last TX was not completed +ERR=1 7
```
```
Preamble value is not allowed. +ERR=1 8
```
```
RX failed, Header error +ERR=
```
```
The time setting value of the “Smart receiving power saving
mode” is not allowed.
```
```
+ERR=
```
```
Syntax Response
AT+FCCT=<Parameter>
<Parameter>range 0 to 2
0 ：Turn off RF.
1 ：Continuously transmit an unmodulated RF signal.
2 ：Continuously transmit a modulated RF signal.
```
#### +OK

```
E-mail: sales@reyax.com
Website : http://reyax.com
```

