//
//  HaoConnect.h
//  HaoxiHttprequest
//
//  Created by lianghuigui on 15/12/3.
//  Copyright © 2015年 lianghuigui. All rights reserved.
//

#import "HaoConfig.h"
#import "HaoResult.h"

#define METHOD_GET @"GET"
#define METHOD_POST @"POST"

extern const NSString * Isdebug;
extern const NSString * Devicetype;
extern const NSString * Requesttime;

@interface HaoConnect : HaoConfig
+ (void)setCurrentUserInfo:(NSString *)userid :(NSString *)loginTime :(NSString *)checkCode;
+ (void)setCurrentDeviceToken:(NSString *)deviceToken;

+ (NSMutableDictionary * )getSecretHeaders:(NSDictionary *)paramDic urlPrame:(NSString *)urlParam;

+ (void)loadContent:(NSString *)urlParam
            params:(NSMutableDictionary *)params
            method:(NSString *)method
      onCompletion:(void (^)(NSData *responseData))completionBlock
           onError:(MKNKErrorBlock)errorBlock;

+ (void)request:(NSString *)urlParam
        params:(NSMutableDictionary *)params
    httpMethod:(NSString *)method
  onCompletion:(void (^)(HaoResult *responseDic))completionBlock
       onError:(void (^)(HaoResult *error))errorBlock;

+ (void)loadJson:(NSString *)urlParam
         params:(NSMutableDictionary *)params
         Method:(NSString *)method
   onCompletion:(void (^)(NSDictionary *responseData))completionBlock
        onError:(MKNKErrorBlock)errorBlock;

+ (void)upLoadImage:(NSString *)urlParam
            params:(NSMutableDictionary *)params
           imgData:(NSData *)imgData
            Method:(NSString *)method
      onCompletion:(void (^)(HaoResult *responseDic))completionBlock
           onError:(void (^)(HaoResult *error))errorBlock;//上传图片

+ (void)canelRequest:(NSString *)urlParam;//取消某个请求
+ (void)canelAllRequest;//取消所有的请求
@end
