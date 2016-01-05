//
//  HaoConnect.m
//  HaoxiHttprequest
//
//  Created by lianghuigui on 15/12/3.
//  Copyright © 2015年 lianghuigui. All rights reserved.
//

#import "HaoConnect.h"
#import "HaoUtility.h"
#import "HaoHttpClient.h"

const NSString * Isdebug      = @"0"; //是否打印调试信息
const NSString * Devicetype   = @"4"; //设备类型 1：浏览器设备 2：pc设备 3：Android设备 4：ios设备 5：windows phone设备
const NSString * Requesttime  = @""; //请求时的时间戳，单位：秒
const NSString * Signature    = @""; //接口加密校验

static NSString * Devicetoken = @""; //推送用的设备token
static NSString * Userid      = @""; //当前用户ID，登录后可获得。
static NSString * Logintime   = @""; //登录时间，时间戳，单位：秒，数据来自服务器
static NSString * Checkcode   = @""; //Userid和Logintime组合加密后的产物，用于进行用户信息加密。数据来自服务器

/**
 *  请求加密后的校验串，服务器会使用同样规则加密请求后，比较校验串是否一致，从而防止请求内容被纂改。
 *  取头信息里Clientversion,Devicetype,Requesttime,Devicetoken,Userid,Logintime,Checkcode,Clientinfo,Isdebug  和 表单数据
 *  每个都使用key=value（空则空字符串）格式组合成字符串然后放入同一个数组
 *  再放入请求地址（去除http://和末尾/?#之后的字符）后
 *  并放入私钥字符串后自然排序
 *  连接为字符串后进行MD5加密，获得Signature
 *  将Signature也放入头信息，进行传输。
 */
@implementation HaoConnect


+ (void)setCurrentUserInfo:(NSString *)userid :(NSString *)loginTime :(NSString *)checkCode{
    
    Userid = userid;
    Logintime = loginTime;
    Checkcode = checkCode;

    [[NSUserDefaults standardUserDefaults] setObject:userid forKey:@"userid"];
    [[NSUserDefaults standardUserDefaults] setObject:loginTime forKey:@"loginTime"];
    [[NSUserDefaults standardUserDefaults] setObject:checkCode forKey:@"checkCode"];


}
+ (void)setCurrentDeviceToken:(NSString *)deviceToken{

    [[NSUserDefaults standardUserDefaults] setObject:deviceToken forKey:@"deviceToken"];

}

//头信息赋值
+ (NSMutableDictionary *)getCommonHeaderInfo{

    NSMutableDictionary * commonParams=[[NSMutableDictionary alloc] init];
    Requesttime                           = [NSString stringWithFormat:@"%.0f",[[NSDate date] timeIntervalSince1970]];
    NSString * version                    = [[[NSBundle mainBundle] infoDictionary] objectForKey:@"CFBundleShortVersionString"];

    if (Devicetoken.length == 0) {
    Devicetoken                           = [[NSUserDefaults standardUserDefaults] objectForKey:@"deviceToken"];
    }
    if (Userid.length == 0) {
    Userid                                = [[NSUserDefaults standardUserDefaults] objectForKey:@"userid"];
    }
    if (Logintime.length == 0) {
    Logintime                             = [[NSUserDefaults standardUserDefaults] objectForKey:@"loginTime"];
    }
    if (Checkcode.length == 0) {
    Checkcode                             = [[NSUserDefaults standardUserDefaults] objectForKey:@"checkCode"];
    }


    [commonParams setObject:  HAOCONNECT_CLIENTINFO     forKey:  @"Clientinfo"];
    [commonParams setObject:  version                   forKey:  @"Clientversion" ];
    [commonParams setObject:  Isdebug                   forKey:  @"Isdebug"];
    [commonParams setObject:  Devicetype                forKey:  @"Devicetype"];
    [commonParams setObject:  Devicetoken               forKey:  @"Devicetoken"];
    [commonParams setObject:  Requesttime               forKey:  @"Requesttime"];
    [commonParams setObject:  Userid                    forKey:  @"Userid"];
    [commonParams setObject:  Logintime                 forKey:  @"Logintime"];
    [commonParams setObject:  Checkcode                 forKey:  @"Checkcode"];

    return commonParams;

}
//头信息加密
+ (NSMutableDictionary * )getSecretHeaders:(NSDictionary *)paramDic urlPrame:(NSString *)urlParam{



    NSMutableArray *array                 = [[NSMutableArray alloc] init];
    NSMutableDictionary *headerDictionary = [NSMutableDictionary dictionaryWithDictionary:[self getCommonHeaderInfo]];

    NSArray *paramKeys                    = [headerDictionary allKeys];
    for (NSString *key in paramKeys) {
        [array addObject:[NSString stringWithFormat:@"%@=%@",key,[headerDictionary objectForKey:key]]];
    }

    for (NSString * key in [paramDic allKeys]) {
        [array addObject:[NSString stringWithFormat:@"%@=%@",key,[paramDic objectForKey:key]]];
    }
    [array addObject:[NSString stringWithFormat:@"link=%@/%@",HAOCONNECT_APIHOST,urlParam]];

    [array addObject:[NSString stringWithFormat:@"%@",HAOCONNECT_SECRET_HAX]];

    NSArray *resultArray                  = [array sortedArrayUsingSelector:@selector(compare:)];
    NSMutableString *secretString         = [NSMutableString string];

    for (NSString *str in resultArray) {
        [secretString appendString:str];
    }

    [headerDictionary setObject:[HaoUtility md5:secretString] forKey:@"Signature"];
    NSLog(@"headerDictionary=%@",headerDictionary);
    return headerDictionary;
}

+ (void)loadContent:(NSString *)urlParam
            params:(NSMutableDictionary *)params
            method:(NSString *)method
      onCompletion:(void (^)(NSData *responseData))completionBlock
           onError:(MKNKErrorBlock)errorBlock

{

    NSDictionary * headers=[self getSecretHeaders:params urlPrame:urlParam];
    NSString * hostName=[NSString stringWithFormat:@"%@/%@",HAOCONNECT_APIHOST,urlParam];
    [HaoHttpClient loadContent:hostName params:params method:method headers:headers onCompletion:^(NSData *responseData) {
        completionBlock(responseData);
    } onError:^(NSError *error) {
        errorBlock(error);
    }];

}

+ (void)request:(NSString *)urlParam
        params:(NSMutableDictionary *)params
    httpMethod:(NSString *)method
  onCompletion:(void (^)(HaoResult *responseDic))completionBlock
       onError:(void (^)(HaoResult *error))errorBlock
{

    [self loadContent:urlParam params:params method:method onCompletion:^(NSData *responseData) {
        @try {
    NSError *err                          = nil;
            NSDictionary * jsonDic=[NSJSONSerialization JSONObjectWithData:responseData options:NSJSONReadingAllowFragments error:&err];
    NSLog(@"jsonDic                       = %@", jsonDic);
            HaoResult * resultData=[HaoResult instanceModel:[jsonDic objectForKey:@"results"] errorCode:[[jsonDic objectForKey:@"errorCode"] integerValue] errorStr:[jsonDic objectForKey:@"errorStr"] extraInfo:[jsonDic objectForKey:@"extraInfo"]];
            if ([resultData isResultsOK]) {
                completionBlock(resultData);
            }else{
                NSLog(@"errorCode==%@",[jsonDic objectForKey:@"errorStr"]);
                errorBlock(resultData);
            }

        }
        @catch (NSException *exception) {
            HaoResult * errorResult=[HaoResult instanceModel:nil errorCode:-1 errorStr:@"JSON解析失败" extraInfo:nil];
            errorBlock(errorResult);

        }
        @finally {

        }

    } onError:^(NSError *error) {
        HaoResult * errorResult=[HaoResult instanceModel:nil errorCode:-1 errorStr:@"JSON解析失败" extraInfo:nil];
        errorBlock(errorResult);
    }];

}

+ (void)loadJson:(NSString *)urlParam
         params:(NSMutableDictionary *)params
         Method:(NSString *)method
   onCompletion:(void (^)(NSDictionary *responseData))completionBlock
        onError:(MKNKErrorBlock)errorBlock
{
    [self loadContent:urlParam params:params method:method onCompletion:^(NSData *responseData) {
        @try {
    NSError *err                          = nil;
            NSDictionary * jsonDic=[NSJSONSerialization JSONObjectWithData:responseData options:NSJSONReadingAllowFragments error:&err];
    NSLog(@"jsonDic                       = %@", jsonDic);
            NSLog(@"errorCode==%@",[jsonDic objectForKey:@"errorStr"]);
            completionBlock(jsonDic);
        }
        @catch (NSException *exception) {
        }
        @finally {

        }

    } onError:^(NSError *error) {
        errorBlock(error);
    }];

}

+ (void)upLoadImage:(NSString *)urlParam
            params:(NSMutableDictionary *)params
           imgData:(NSData *)imgData
            Method:(NSString *)method
      onCompletion:(void (^)(HaoResult *responseDic))completionBlock
           onError:(void (^)(HaoResult *error))errorBlock{
    NSDictionary * headers=[self getSecretHeaders:params urlPrame:nil];
//    NSString * hostName=[NSString stringWithFormat:@"%@/%@",HAOCONNECT_APIHOST,urlParam];

    [HaoHttpClient uploadImage:urlParam params:params imageDatas:imgData Method:method headers:headers onCompletion:^(NSData *responseData) {
        @try {
    NSError *err                          = nil;
            NSDictionary * jsonDic=[NSJSONSerialization JSONObjectWithData:responseData options:NSJSONReadingAllowFragments error:&err];
    NSLog(@"jsonDic                       = %@", jsonDic);
            HaoResult * resultData=[HaoResult instanceModel:[jsonDic objectForKey:@"results"] errorCode:[[jsonDic objectForKey:@"errorCode"] integerValue] errorStr:[jsonDic objectForKey:@"errorStr"] extraInfo:[jsonDic objectForKey:@"extraInfo"]];
            if ([resultData isResultsOK]) {
                completionBlock(resultData);
            }else{
                NSLog(@"errorCode==%@",[jsonDic objectForKey:@"errorStr"]);
                errorBlock(resultData);
            }

        }
        @catch (NSException *exception) {
            HaoResult * errorResult=[HaoResult instanceModel:nil errorCode:-1 errorStr:@"JSON解析失败" extraInfo:nil];
            errorBlock(errorResult);

        }
        @finally {

        }


    } onError:^(NSError *error) {
        HaoResult * errorResult=[HaoResult instanceModel:nil errorCode:-1 errorStr:@"JSON解析失败" extraInfo:nil];
        errorBlock(errorResult);
    }];

}
+ (void)canelRequest:(NSString *)urlParam{
    [HaoHttpClient canelRequest:urlParam];
}
+ (void)canelAllRequest{
    [HaoHttpClient canelAllRequest:(NSString *)HAOCONNECT_APIHOST];
}
@end
