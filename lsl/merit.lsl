// --------------------------- CONFIGURATION ---------------------------------

string SERVER = "http://merit.sourceforge.net/api.php";
string SECRET = "changeme";
integer DEBUG = FALSE;

// ------------------------- BEGIN OF SCRIPT ---------------------------------

string VERSION = "0.0.1";
list LISTENER_HANDLES;
list LISTENER_CHANNELS;
list LISTENER_IDENTIFIERS;
list LISTENER_TIMESTAMPS;

debug(string message)
{
    if (DEBUG)
    {
        llOwnerSay("DEBUG: " + message);
    }
}


/**
 * sends a message to the server backend
 *
 * @param data the command (at index 0) and parameters
 */
send_request(list data)
{
    debug(">" + llList2Json(JSON_ARRAY, data));
    llHTTPRequest(SERVER, [HTTP_METHOD, "POST", HTTP_CUSTOM_HEADER, "X-Secret", SECRET], llList2Json(JSON_ARRAY, data));
}

/**
 * gets the id of the currently active group of the specified agent.
 * For this function to work, the agent needs to be near by and has to wear at least one non-hud attachment
 *
 * @param agentid id of an agent
 * @return id of current active group of that agent
 */
key agent_group(key agentid)
{
    list attachedUUIDs = llGetAttachedList(agentid);
    if (llGetListLength(attachedUUIDs) > 0)
    {
        list temp = llGetObjectDetails(llList2Key(attachedUUIDs, 0), [OBJECT_GROUP]);
        return llList2Key(temp, 0);
    }
    return NULL_KEY;
}

/**
 * allocates a listener on a random positive channel.
 * A possitive channel is picked for RLV compatibility.
 *
 * @param identifier identifier provided by the server
 * @param id agent or object to listen to
 * @return channel number
 */
integer allocate_listener(integer identifier, key id)
{
    // Create random channel within range [1000000000, 2000000000]
    integer channel = (integer)(llFrand(1000000000.0) + 1000000000.0);
    LISTENER_CHANNELS    += channel;
    LISTENER_IDENTIFIERS += identifier;
    LISTENER_TIMESTAMPS  += llGetUnixTime();
    LISTENER_HANDLES     += llListen(channel, "", id, "");

    // start timer to handle timeouts, if it is not already running
    if (llGetListLength(LISTENER_TIMESTAMPS) == 1)
    {
        llSetTimerEvent(60.0);
    }
    return channel;
}

/**
 * removes a listener and cleans up all the meta information
 *
 * @param i index of listener
 */
cleanup_listener(integer i)
{
    llListenRemove(llList2Integer(LISTENER_HANDLES, i));
    LISTENER_CHANNELS    = llDeleteSubList(LISTENER_CHANNELS, i, i);
    LISTENER_IDENTIFIERS = llDeleteSubList(LISTENER_IDENTIFIERS, i, i);
    LISTENER_TIMESTAMPS  = llDeleteSubList(LISTENER_TIMESTAMPS, i, i);
    LISTENER_HANDLES     = llDeleteSubList(LISTENER_HANDLES, i, i);
}


/**
 * executes a command
 *
 * @param data a command (at index 0) and its parameter
 */
execute_command(list data)
{
    string command = llList2String(data, 0);
    if (command == "ownersay")
    {
        llOwnerSay(llList2String(data, 1));
    }
    else if (command == "say")
    {
        llSay(llList2Integer(data, 1), llList2String(data, 2));
    }
    else if (command == "whisper")
    {
        llWhisper(llList2Integer(data, 1), llList2String(data, 2));
    }
    else if (command == "shout")
    {
        llShout(llList2Integer(data, 1), llList2String(data, 2));
    }
    else if (command == "regionsay")
    {
        llRegionSay(llList2Integer(data, 1), llList2String(data, 2));
    }
    else if (command == "regionsayto")
    {
        llRegionSayTo(llList2Key(data, 1), llList2Integer(data, 2), llList2String(data, 3));
    }
    else if (command == "instantmessage")
    {
        llInstantMessage(llList2Key(data, 1), llList2String(data, 2));
    }
    else if (command == "loadurl")
    {
        llLoadURL(llList2Key(data, 1), llList2String(data, 2), llList2String(data, 3));
    }
    else if (command == "dialog")
    {
        integer channel = allocate_listener(llList2Integer(data, 1), llList2Key(data, 2));
        llDialog(llList2Key(data, 2), llList2String(data, 3), llJson2List(llList2String(data, 4)), channel);
    }
    else if (command == "textbox")
    {
        integer channel = allocate_listener(llList2Integer(data, 1), llList2Key(data, 2));
        llTextBox(llList2Key(data, 2), llList2String(data, 3), channel);
    }
    else if (command == "settext")
    {
        llSetText(llList2String(data, 1), llList2Vector(data, 2), llList2Float(data, 3));
    }
    else if (command == "setobjectname")
    {
        llSetObjectName(llList2String(data, 1));
    }
    else if (command == "setobjectdesc")
    {
        llSetObjectDesc(llList2String(data, 1));
    }
}



default
{
    state_entry()
    {
        
    }

    /**
     * reset script on owner change to clean up all pending listeners
     */
    changed(integer Changed)
    {
        if (Changed & CHANGED_OWNER)
        {
            llResetScript();
        }
        send_request(["changed", Changed]);
    }

    /**
     * reports an answer (e. g. from a dialog or message box) to the server and cleans up the listener
     */
    listen(integer channel, string name, key id, string message)
    {
        integer i = llListFindList(LISTENER_CHANNELS, [channel]);
        send_request(["listen", llList2Integer(LISTENER_IDENTIFIERS, i), id, name, agent_group(id), message]);
        cleanup_listener(i);
    }

    /**
     * reports rezzing to the server with the current version number.
     * the server will most likely answer with a set on commands to be executed on login
     */
    on_rez(integer startParameter)
    {
        send_request(["on_rez", startParameter, VERSION]);
    }

    /**
     * cleans up listeners (e. g. if an avatar closed a dialog box with "Ignore"
     */
    timer()
    {
        integer compare = llGetUnixTime() - 5*60;
        integer i;
        integer len = llGetListLength(LISTENER_TIMESTAMPS);
        for (i = len - 1; i >= 0; i--)
        {
            if (llList2Integer(LISTENER_TIMESTAMPS, i) < compare)
            {
                send_request(["timeout_listener", llList2Integer(LISTENER_IDENTIFIERS, i)]);
                cleanup_listener(i);
            }
        }

        // if there are no more pending listeners, stop the timer events
        if (llGetListLength(LISTENER_TIMESTAMPS) == 0)
        {
            llSetTimerEvent(0.0);
        }
    }

    /**
     * reports a touch event to the server, which will most likely answer with
     * with a command to display a dialog, after checking permissions
     */
    touch_start(integer total_number)
    {
        send_request(["touch", llDetectedKey(0), llDetectedName(0), agent_group(llDetectedKey(0))]);
    }

    /**
     * handles a response from the server by splitting it into invidiual 
     * commands which will be executed by execute_command()
     */
    http_response(key request_id, integer status, list metadata, string body)
    {
        debug("< " + body);
        integer i;
        list commands = llJson2List(body);
        for (i = 0; i < llGetListLength(commands); i++)
        {
            execute_command(llJson2List(llList2String(commands, i)));
        }
    }
    
}
