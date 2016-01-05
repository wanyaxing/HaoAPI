//
//  HaoHttpClient.m
//  HaoxiHttprequest
//
//  Created by lianghuigui on 15/11/29.
//  Copyright © 2015年 lianghuigui. All rights reserved.
//

#import "HaoHttpClient.h"
#import "HaoConnect.h"
#import "HaoUtility.h"
@implementation HaoHttpClient

+ (void)loadContent:(NSString *)actionUrl
            params:(NSMutableDictionary *)params
            method:(NSString *)method
           headers:(NSDictionary *)headers
      onCompletion:(void (^)(NSData *responseData))completionBlock
           onError:(MKNKErrorBlock)errorBlock
{

    MKNetworkEngine * engine = [[MKNetworkEngine alloc] initWithHostName:actionUrl customHeaderFields:headers];
    MKNetworkOperation *op =[engine operationWithPath:nil params:params httpMethod:method ssl:NO];
    NSLog(@"url=%@",op.url);
    [op addCompletionHandler:^(MKNetworkOperation *operation){
    NSData *responseData     = [operation responseData];
        completionBlock(responseData);
    } errorHandler:^(MKNetworkOperation* completedOperation, NSError* error){
        errorBlock(error);
    }];
    [engine enqueueOperation:op];


}
+ (void)loadJson:(NSString *)actionUrl
        params:(NSMutableDictionary *)params
        Method:(NSString *)method
       headers:(NSDictionary *)headers
  onCompletion:(void (^)(NSDictionary *responseData))completionBlock
       onError:(MKNKErrorBlock)errorBlock
{
    [self loadContent:actionUrl params:params method:method headers:headers onCompletion:^(NSData *responseData) {
    NSError *err             = nil;
        NSDictionary * jsonDic=[NSJSONSerialization JSONObjectWithData:responseData options:NSJSONReadingAllowFragments error:&err];
    NSLog(@"jsonDic          = %@", jsonDic);
        NSLog(@"errorCode==%@",[jsonDic objectForKey:@"errorStr"]);
        completionBlock(jsonDic);

    } onError:^(NSError *error) {
        errorBlock(error);
    }];
}

//上传图片
+ (MKNetworkOperation*) uploadImage:(NSString *)actionUrl
                            params:(NSMutableDictionary *)params
                        imageDatas:(NSData *)imgData
                            Method:(NSString *)method
                           headers:(NSDictionary *)headers
                      onCompletion:(void (^)(NSData * responseData))completionBlock
                           onError:(MKNKErrorBlock)errorBlock
{

    MKNetworkEngine *engine  = [[MKNetworkEngine alloc] initWithHostName:actionUrl customHeaderFields:headers];

    MKNetworkOperation *op   = [engine operationWithPath:nil
                                                params:params
                                            httpMethod:method];
    [op addData:imgData forKey:@"file" mimeType:@"image/jpeg" fileName:[NSString stringWithFormat:@"%.0f.jpg",[[NSDate date] timeIntervalSince1970]]];



    // setFreezable uploads your images after connection is restored!

    [op setFreezable:YES];


    [op addCompletionHandler:^(MKNetworkOperation* completedOperation) {
    NSData *responseData     = [completedOperation responseData];
        completionBlock(responseData);
    } errorHandler:^(MKNetworkOperation *errorOp, NSError* err){
        errorBlock(err);
    }];



    [engine enqueueOperation:op];



    return op;

}

+ (void)canelRequest:(NSString *)urlParam{

    [MKNetworkEngine cancelOperationsContainingURLString:urlParam];

}

+ (void)canelAllRequest:(NSString *)actionUrl{

    [MKNetworkEngine cancelOperationsContainingURLString:actionUrl];

}

@end
