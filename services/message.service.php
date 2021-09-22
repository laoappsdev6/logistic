<?php

class Message
{
    const socketRuning = "✨ 🎃 💘  Socket server is runing...  💘 🎃 ✨ on port: ";
    const httpRuning = "✨ 🎃 💘  HTTP server is runing...  💘 🎃 ✨ on port: ";
    const tcpRuning = "✨ 🎃 💘  TCP server is runing..  💘 🎃 ✨ on port: ";
    const clientConnect = "✅✅ Client connection ✅✅ Id: ";
    const clientClose = "❌❌ Client close ❌❌ Id: ";
    const onMessage = "📩 📨 📧  Message from client  📧 📨 📩 Id: ";
    const reply = "🚀 🚀 🛫  Reply to client  🛫 🚀 🚀 Id: ";
    const queryConnectFail = "❌🚫 Sorry, Canot connect to query server 🚫❌";


    const objectNotFound = "Object not found!";
    const methodNotFound = "Method not found!";
    const noAuthorize = "You have no authorize";
    const noUseSystem = "Sorry, You do not have access to the system!";
    const noToken = "You have no token";
    const wrongUserOrPass = "Wrong username or password!";
    const userEmpty = "Username is empty!";
    const passEmpty = "Password is empty!";
    const emptyUserAndPass = "Username and password are empty!";
    const loginOk = "Login Successfully!";
    const dataEmpty = "Data is empty!";


    const addSuccess = "Add Data Successfully.";
    const addFail = "Add Data Fail!";
    const updateSuccess = "Update data successfully.";
    const updateFail = "Update data fail!";
    const deleteSuccess = "Delete data successfully.";
    const deleteFail = "Delete data fail!";
    const changePasswordSuccess = "Change Password Successfully.";
    const changePasswordFail = "Change Password Fail!";


    const listAll = "Data list all";
    const listPage = "Data list page";
    const listOne = "Data list one";
    const listReport = "Data list report";


    const objEmpty = "Data is empty!";
    const empty = " is empty!";
    const already = " already exists!";
    const exists = " is not exists!";
    const date = " is not date format!";
    const time = " is not time format!";
    const dateTime = " is not date time format!";
    const number = " is number only!";
    const notEqual = " is not equal ";
    const mustBeThan = " must be more than ";
    const validationError = "Validation error!";
}

class Status
{
    const success = 1;
    const fail = 0;
}
