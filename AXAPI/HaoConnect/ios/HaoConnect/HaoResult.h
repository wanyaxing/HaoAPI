//
//  HaoResult.h
//  HaoxiHttprequest
//
//  Created by lianghuigui on 15/12/22.
//  Copyright © 2015年 lianghuigui. All rights reserved.
//

#import <Foundation/Foundation.h>

@interface HaoResult : NSObject
@property (nonatomic,assign) NSInteger   errorCode;
@property (nonatomic,strong) NSString  * errorStr;
@property (nonatomic,strong) id          extraInfo;
@property (nonatomic,strong) id          results;
@property (nonatomic,strong) NSString  * modelType;
@property (nonatomic,strong) NSString  * searchIndexString;


@property (nonatomic,strong) NSMutableDictionary * pathCache;

+(id)instanceModel:(id)results errorCode:(NSInteger)errorCode errorStr:(NSString *)errorStr extraInfo:(id)extraInfo;
-(id)find:(NSString *)path;
-(NSArray *)findAsList:(NSString *)path;
-(NSString *)findAsString:(NSString *)path;
-(HaoResult *)findAsResult:(NSString *)path;
-(NSDictionary *)properties;
-(NSArray *)search:(NSString *)path;
-(BOOL)isModelType:(NSString *)modelType;
-(BOOL)isErrorCode:(NSInteger)errorCode;
-(BOOL)isResultsOK;
@end
