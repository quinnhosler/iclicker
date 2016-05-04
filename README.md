# iClicker
A Tsugi module meant to replace premium instant polling services such as i>clicker and PollEverywhere


## Features
This module features the ability for teachers and instructors get instant feedback from students and gain insight into potential misunderstandings.

This module features the ability for instructors to
  * Prepare questions before lecture
  * Copy and repeat polls
  * Quickly initiate preset polls
  * Show students countdown timer
  * Reveal poll results while keeping future questions hidden

Best of all, this module is free (as in beer and speech)!

## Installation
Installation of this module is easy. Simply run the following command within the `/var/www/html/tsugi/mod/` directory.
```
git clone https://github.com/quinnhosler/iclicker.git
```
Once the command is run, navigate to the admin page within the Tsugi module and click `Upgrade Database`. Your module should now be fully operational!

However, in order to enable realtime communication and avoid a warning message on polls, a second application is required to allow communication via websockets. This application is simple and can be installed via the following commands
```
git clone https://github.com/quinnhosler/pusher.git
cd pusher
bash install.sh
```

Please install this application in an area that makes sense. It will work from anywhere, but should not be accessible by the Apache server.

Once installed, the application can be start with the following command from within the `pusher` directory
```
nohup ./start.sh >> pusher.log &
```

This application is remarkable stable and should not need to be restarted except upon system reboot.

## Usage
Usage of the Tsugi iClicker module is designed to be as intuitive as possible, although it does have its quirks.

#### Student View
From the student view, operation is dead simple. Once a poll is initiated, all the student has to do to submit an answer is click on his/her choice. The button should appear red when the response is successfully recorded. A student is free to change their answer so long as the poll is active. Which ever answer is colored red is the answer that is recorded in the system. If a yellow warning box is present as the top of the page, the student was unable to successfully connect to the websocket interface. If this is the case, the student will need refresh the page to view newly initiated polls. The buttons will also not disable upon retraction if not connected to the websocket.

#### Instructor View
The instructor view is significantly more complex than the student view, although many of the functions are straight forward. To initiate a default poll, simply click on `New Quick Poll` and select the poll type you would like. Upon selection, a 60 second timer will initiate, and upon completion the poll will be automatically retracted and the results displayed. If a customer poll is desired, the `Custom Poll` option can be selected from `New Quick Poll`. Upon selection, a form will appear that will allow the instructor to input an optional poll title, and a minimum of two choices. An error will be displayed if a field is left empty. More choices can be added with the `Add Choice` button. Leaving the default options, clicking the `Start` button will initiate the poll immediately and begin a 60 second timer. If the poll is designed to be used later, deselection of the `Start Immediately` checkbox will save the poll to the pending table.

If a poll is active, the timer can be stopped by selecting the `Pause` link under `Controls` in the navbar. This will pause the timer, but allow the poll to remain active. The `Start` link will restart the timer, and the poll will be retraction upon reaching 0.

If the instructor wishes to retract the poll, there are two options. The first is to click the `Stop` link under `Controls`. This action will only be successful if the timer for the current poll is being displayed. Clicking the `Retract` link in the `Active` table will also terminate the poll and display results. This action should always be successful.

Viewing results of polls is a simple as clicking as the row in the table corresponding to the poll you would like to view. A bar graph will then appear at the top of the page. If you wish to show students the timer and results, but not potentially sensitive information on you screen, click the `Results Window` on the right hand side of the navbar. This link will spawn a new window which will display results and timers as they are initiated from the main page *on the same machine*. Be warned if a poll is selected while a timer is ongoing, the timer will disappear, the result will be displayed, but the timer will still terminate in the remaining time so long as the `Pause` button is not clicked.

Finally, this module features the ability to copy an existing poll. Simply click on the poll you would like to copy and select the `Repeat Poll` link in the navbar. This link will add the poll to the pending section of the page. A refresh may be needed to display fully.

## Compatibility

The iClicker module is designed to only work with modern browsers. As a general rule of thumb, do not expect it to work with Internet Explorer. Other browsers such as Chrome, Firefox, and Safari should work, given they are up to date. The module has also been tested on mobile Safari, but not on android's native browser although it is expected to work.

## Limitations

  * may not work with all browsers
  * choices may appeared unordered depending on state of machine
  * requires extra module for push polls
  * requires multiple API calls per action by instructors
  * currently no way to count correct answers, although backend code is in place to do so.
  * no way for students to view past responses
  * no way to know which poll timer corresponds to
  *
