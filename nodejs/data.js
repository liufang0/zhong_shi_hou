// Railway 部署时，NODE_SCHEDULER_HOST 指向内部服务地址（如 localhost）
// 本地开发时 fallback 到原地址
var localurl = process.env.NODE_SCHEDULER_HOST || '38.246.113.26',
    // Railway 内部走 http，外部走 https
    http = process.env.NODE_SCHEDULER_HOST ? require('http') : require('https'),
    schedule = require('node-schedule'),
	headers = {"User-Agent": "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36"};

// Railway 内部端口
var localport = parseInt(process.env.NODE_SCHEDULER_PORT || '8080');

// 定时器，每1秒执行
schedule.scheduleJob('*/1 * * * * *', function(){
    var option = {
            host: localurl,
            port: localport,
            timeout: 5000,
            path: '/index/index/order',
            headers: headers
        };
    http.request(option, function (res) {
        var data = "";
        res.on("data", function (_data) {
            
        });
        res.on("end", function () {
            
        });
    }).end();
});

// 定时器，每1秒执行
schedule.scheduleJob('*/1 * * * * *', function(){
    var option = {
            host: localurl,
            port: localport,
            timeout: 5000,
            path: '/index/index/product',
            headers: headers
        };
    http.request(option, function (res) {
        var data = "";
        res.on("data", function (_data) {
            
        });
        res.on("end", function () {
            
        });
    }).end();
});

// 定时器，每1秒执行
schedule.scheduleJob('*/1 * * * * *', function(){
    var option = {
            host: localurl,
            port: localport,
            timeout: 5000,
            path: '/index/index/yebeveryday?token=ABCD484088',
            headers: headers
        };
    http.request(option, function (res) {
        var data = "";
        res.on("data", function (_data) {
            
        });
        res.on("end", function () {
            
        });
    }).end();
});

// 每日 00:00:00 执行：快照昨日余额/昨日收益（prebalance/preprofit）
// 对应控制器 Index::upuceveryday1()，必须每天只跑一次，否则“昨天”语义会被覆盖
schedule.scheduleJob('0 0 0 * * *', function(){
    var option = {
            host: localurl,
            port: localport,
            timeout: 5000,
            path: '/index/index/upuceveryday1?token=ABCD484088',
            headers: headers
        };
    var req = http.request(option, function (res) {
        var data = "";
        res.on("data", function (_data) {
            
        });
        res.on("end", function () {
            
        });
    });
    req.on("error", function () {});
    req.end();
});

// 每日 00:00:30 执行：重算累计收益（totalprofit），晚 30 秒确保先完成上面的快照
// 对应控制器 Index::upuceveryday2()
schedule.scheduleJob('30 0 0 * * *', function(){
    var option = {
            host: localurl,
            port: localport,
            timeout: 5000,
            path: '/index/index/upuceveryday2?token=ABCD484088',
            headers: headers
        };
    var req = http.request(option, function (res) {
        var data = "";
        res.on("data", function (_data) {
            
        });
        res.on("end", function () {
            
        });
    });
    req.on("error", function () {});
    req.end();
});