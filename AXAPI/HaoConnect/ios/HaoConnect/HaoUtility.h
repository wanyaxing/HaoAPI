//
//  HaoUtility.h
//  HaoxiHttprequest
//
//  Created by lianghuigui on 15/12/21.
//  Copyright © 2015年 lianghuigui. All rights reserved.
//

#import <Foundation/Foundation.h>

@interface HaoUtility : NSObject
+ (NSString*)md5:(NSString *)inputStr;
+(NSArray *)getKeyIndexArray:(id)dic;
+(NSString *)md5FileData:(NSData *)fileData;
@end
