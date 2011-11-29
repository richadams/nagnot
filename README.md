NagNOT
------

A (very) simple Nagios Notifier script.

I was sick of getting an SMS all the time when an IM would do. So this just sends an IM if it can, 
otherwise triggers an email to your SMS address. Basically, I don't want to get a SMS if I've already
received an IM. But if I'm not logged into IM, then I still want to get a SMS.

Rich Adams <rich@richadams.me> (http://richadams.me)
Licensed under the GPL.

Uses XMPPHP http://code.google.com/p/xmpphp/

----------------------------------------------------------------------------------------------------

To use, modify the .php file to setup your XMPP server settings, then just add the following stuff 
to your Nagios config in the usual places,

    define command {
        command_name    host-notify-via-nagnot
        command_line    /path/to/nagnot.sh -i "$CONTACTEMAIL$" -e "$CONTACTPAGER$" -s "$NOTIFICATIONTYPE$: $HOSTNAME$ is $HOSTSTATE$" -m "Host: $HOSTALIAS$, State: $HOSTSTATE$, Info: $HOSTOUTPUT$, Date/Time: $DATE$ $TIME$"
    }

    define command {
        command_name    service-notify-via-nagnot
        command_line    /path/to/nagnot.sh -i "$CONTACTEMAIL$" -e "$CONTACTPAGER$" -s "$NOTIFICATIONTYPE$: $HOSTALIAS$/$SERVICEDESC$ is $SERVICESTATE$" -m "Service: $SERVICEDESC$, Host: $HOSTNAME$, Address: $HOSTADDRESS$, State: $SERVICESTATE$, Info: $SERVICEOUTPUT$, Date/Time: $DATE$ $TIME$"
    }

    define contact {
        ...
        host_notification_commands      host-notify-via-nagnot
        service_notification_commands   service-notify-via-nagnot
        email                           <your IM address>
        pager                           <your SMS email>
	    ...
    }
