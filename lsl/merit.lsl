string SERVER = "http://merit.sourceforge.net/api.php";


send_request(list data)
{
    llHTTPRequest(SERVER, [HTTP_METHOD, "POST"], llList2Json(JSON_ARRAY, data));
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


integer allocate_listener(integer identifier)
{
    // TODO
    return identifier;
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
        integer channel = allocate_listener(llList2Integer(data, 1));
        llDialog(llList2Key(data, 2), llList2String(data, 3), llJson2List(llList2String(data, 4)), channel);
    }
    else if (command == "textbox")
    {
        integer channel = allocate_listener(llList2Integer(data, 1));
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
        send_request(["changed", Changed]);
    }

    listen(integer channel, string name, key id, string message)
    {
        
    }

    on_rez(integer startParameter)
    {
        send_request(["on_rez", startParameter]);
    }

    timer()
    {
    }

    touch_start(integer total_number)
    {
        send_request(["touch", llDetectedKey(0), llDetectedName(0), llDetectedGroup(0), agent_group(llDetectedKey(0))]);
    }

    http_response(key request_id, integer status, list metadata, string body)
    {
        integer i;
        list commands = llJson2List(body);
        for (i = 0; i < llGetListLength(commands); i++)
        {
            execute_command(llJson2List(llList2String(commands, i)));
        }
    }
    
}

