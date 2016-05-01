// --------------------------- CONFIGURATION ---------------------------------

string SERVER = "http://merit.sourceforge.net/api.php";
string SECRET = "changeme";

// ------------------------- BEGIN OF SCRIPT ---------------------------------

string VERSION = "0.0.1";
list LISTENER_HANDLES;
list LISTENER_CHANNELS;
list LISTENER_IDENTIFIERS;
list LISTENER_TIMESTAMPS;

send_request(list data)
{
    llHTTPRequest(SERVER, [HTTP_METHOD, "POST", HTTP_CUSTOM_HEADER, "X-Secret", SECRET], llList2Json(JSON_ARRAY, data));
}


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


integer allocate_listener(integer identifier, key id)
{
    // Create random channel within range [1000000000, 2000000000]
    integer channel = (integer)(llFrand(1000000000.0) + 1000000000.0);
    LISTENER_CHANNELS    += channel;
    LISTENER_IDENTIFIERS += identifier;
    LISTENER_TIMESTAMPS  += llGetUnixTime();
    LISTENER_HANDLES     += llListen(channel, "", id, "");

    if (llGetListLength(LISTENER_TIMESTAMPS) == 1)
    {
        llSetTimerEvent(60.0);
    }
    return channel;
}


cleanup_listener(integer i)
{
    llListenRemove(llList2Integer(LISTENER_HANDLES, i));
    LISTENER_CHANNELS    = llDeleteSubList(LISTENER_CHANNELS, i, i);
    LISTENER_IDENTIFIERS = llDeleteSubList(LISTENER_IDENTIFIERS, i, i);
    LISTENER_TIMESTAMPS  = llDeleteSubList(LISTENER_TIMESTAMPS, i, i);
    LISTENER_HANDLES     = llDeleteSubList(LISTENER_HANDLES, i, i);
}


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
    else if (command == "shout")
    {
        llShout(llList2Integer(data, 1), llList2String(data, 2));
    }
}



default
{
    state_entry()
    {
        
    }

    changed(integer Changed)
    {
        if (Changed & CHANGED_OWNER)
        {
            llResetScript();
        }
        send_request(["changed", Changed]);
    }

    listen(integer channel, string name, key id, string message)
    {
        integer i = llListFindList(LISTENER_CHANNELS, [channel]);
        send_request(["listen", llList2Integer(LISTENER_IDENTIFIERS, i), id, name, agent_group(id), message]);
        cleanup_listener(i);
    }

    on_rez(integer startParameter)
    {
        send_request(["on_rez", startParameter, VERSION]);
    }

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

        if (llGetListLength(LISTENER_TIMESTAMPS) == 0)
        {
            llSetTimerEvent(0.0);
        }
    }

    touch_start(integer total_number)
    {
        send_request(["touch", llDetectedKey(0), llDetectedName(0), agent_group(llDetectedKey(0))]);
    }

    http_response(key request_id, integer status, list metadata, string body)
    {
        llOwnerSay("!" + body);
        integer i;
        list commands = llJson2List(body);
        for (i = 0; i < llGetListLength(commands); i++)
        {
            execute_command(llJson2List(llList2String(commands, i)));
        }
    }
    
}
