//
//  HaoHttpClient.h
//  HaoxiHttprequest
//
//  Created by lianghuigui on 15/11/29.
//  Copyright © 2015年 lianghuigui. All rights reserved.
//

#import <Foundation/Foundation.h>
#import "MKNetworkEngine.h"
#import "HaoResult.h"
@interface HaoHttpClient : NSObject
typedef void (^ResponseDataBlock)(id);

+ (void)loadContent:(NSString *)actionUrl
            params:(NSMutableDictionary *)params
            method:(NSString *)method
           headers:(NSDictionary *)headers
      onCompletion:(void (^)(NSData *responseData))completionBlock
           onError:(MKNKErrorBlock)errorBlock;

+ (void)loadJson:(NSString *)actionUrl
         params:(NSMutableDictionary *)params
         Method:(NSString *)method
        headers:(NSDictionary *)headers
   onCompletion:(void (^)(NSDictionary *responseData))completionBlock
        onError:(MKNKErrorBlock)errorBlock;

+ (MKNetworkOperation*) uploadImage:(NSString *)actionUrl
                            params:(NSMutableDictionary *)params
                        imageDatas:(NSData *)imgData
                            Method:(NSString *)method
                           headers:(NSDictionary *)headers
                      onCompletion:(void (^)(NSData * responseData))completionBlock
                           onError:(MKNKErrorBlock)errorBlock;

+ (void)canelRequest:(NSString *)urlParam;
+ (void)canelAllRequest:(NSString *)actionUrl;
@end
