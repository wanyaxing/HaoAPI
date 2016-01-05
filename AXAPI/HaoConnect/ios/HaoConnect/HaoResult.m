//
//  HaoResult.m
//  HaoxiHttprequest
//
//  Created by lianghuigui on 15/12/22.
//  Copyright © 2015年 lianghuigui. All rights reserved.
//

#import "HaoResult.h"
#import "HaoUtility.h"
@implementation HaoResult

+(id)instanceModel:(id)results errorCode:(NSInteger)errorCode errorStr:(NSString *)errorStr extraInfo:(id)extraInfo{

    
    NSString * modelType=@"HaoResult";
    
    if ([results isKindOfClass:[NSDictionary class]]) {
        if ([[(NSDictionary *)results allKeys] containsObject:@"modelType"]) {
            modelType=[(NSDictionary *)results objectForKey:@"modelType"];
        }
    }
    
    
    NSString * resultName=[modelType isEqualToString:@"HaoResult"]?modelType:[modelType stringByAppendingString:@"Result"];
    
    HaoResult * object=[[NSClassFromString(resultName) alloc] init];
    
    if (object==nil) {
        object=[[HaoResult alloc] init];
    }
    
    object.results   = results;
    object.errorCode = errorCode;
    object.errorStr  = errorStr;
    object.extraInfo = extraInfo;
    object.modelType = modelType;

    object.pathCache =[[NSMutableDictionary alloc] init];

    return object;
}

-(id)find:(NSString *)path{
    
    
    path  = [path stringByTrimmingCharactersInSet:[NSCharacterSet whitespaceAndNewlineCharacterSet]];
    
    if ([[self.pathCache allKeys] containsObject:path]) {
        return [self.pathCache objectForKey:path];
    }
    
    BOOL isorBeginResults=[path hasPrefix:@"results>"];
    BOOL isorBeginExtraInfo=[path hasPrefix:@"extraInfo>"];
    if (!isorBeginExtraInfo&&!isorBeginResults) {
        path=[@"results>" stringByAppendingString:path];
    }

    NSArray * keys=[path componentsSeparatedByString:@">"];
    id changeValue;
    
    for (int i=0; i<[keys count]; i++) {
        NSString * keyItem=keys[i];
        if (i==0) {
            if ([keyItem isEqualToString:@"extraInfo"]) {
                changeValue=self.extraInfo;

            }else{
                
                changeValue=self.results;
            }
        }else{
            
            if(keyItem.length>0){
                if ([[NSString stringWithFormat:@"%ld",[keyItem integerValue]] isEqualToString:keyItem]) {
                    if ([changeValue isKindOfClass:[NSArray class]]) {
                        changeValue=[(NSArray *)changeValue objectAtIndex:[keyItem integerValue]];
                        continue;
                    }
                }else{
                    
                    if ([changeValue isKindOfClass:[NSDictionary class]]) {
                        
                        if ([[(NSDictionary *)changeValue allKeys] containsObject:keyItem]) {
                            changeValue=[(NSDictionary *)changeValue objectForKey:keyItem];
                            continue;
                        }
                        
                    }
                }
                
                    changeValue = nil;
                    break;

                
            }
            
        }
        

    }
    id object = [self value:changeValue];
    
    [self.pathCache setObject:object forKey:path];
    
    return object;
    
    
}
-(id)value:(id)changeValue{

    if ([changeValue isKindOfClass:[NSDictionary class]]) {
        if ([[(NSDictionary *)changeValue allKeys] containsObject:@"modelType"]) {
            HaoResult * newResult=[HaoResult instanceModel:changeValue errorCode:self.errorCode errorStr:self.errorStr extraInfo:self.extraInfo];
            return newResult;
            
            
        }
    }else if ([changeValue isKindOfClass:[NSArray class]]){
    
        NSMutableArray * newResults=[NSMutableArray array];
        NSArray * changeValueArray=(NSArray *)changeValue;
        for (id item in changeValueArray) {
            if ([item isKindOfClass:[NSDictionary class]]&&[[(NSDictionary *)item allKeys] containsObject:@"modelType"]) {
                HaoResult * newResult=[HaoResult instanceModel:item errorCode:self.errorCode errorStr:self.errorStr extraInfo:self.extraInfo];
                [newResults addObject:newResult];
            }else{
                [newResults addObject:item];
            }
        }
        
        return newResults;
    }
    
    return changeValue;
}
-(NSArray *)findAsList:(NSString *)path{

    id object = [self find:path];
    
    if (![object isKindOfClass:[NSArray class]]) {
        return @[object];
    }
        return (NSArray *)object;
}
-(NSString *)findAsString:(NSString *)path{

    id object = [self find:path];
    
    return [NSString stringWithFormat:@"%@",object];
}
-(HaoResult *)findAsResult:(NSString *)path{

    id object = [self find:path];
    
    if (![object isKindOfClass:[HaoResult class]]) {
        HaoResult * newResult=[HaoResult instanceModel:object errorCode:self.errorCode errorStr:self.errorStr extraInfo:self.extraInfo];
        return newResult;
    }
    
    return (HaoResult *)object;
    
}

-(NSDictionary *)properties{

    NSDictionary * dic=nil;
    dic=[NSDictionary dictionaryWithObjectsAndKeys:
         [NSString stringWithFormat:@"%ld",self.errorCode],@"errorCode",
         self.errorStr,@"errorStr",
         self.extraInfo,@"extraInfo",
         self.results,@"results",
         nil];
    return dic;
}



-(NSArray *)search:(NSString *)path{

    if (_searchIndexString==nil) {
        
        NSDictionary * exprameResult=[NSDictionary dictionaryWithObjectsAndKeys:_results,@"results", nil];
        NSDictionary * exprameExtraInfo=[NSDictionary dictionaryWithObjectsAndKeys:_extraInfo,@"extraInfo", nil];
        
        
        NSArray * indeOfResultArray=[HaoUtility getKeyIndexArray:exprameResult];
        NSArray * indeOfExtraInfo=[HaoUtility getKeyIndexArray:exprameExtraInfo];
        
        NSArray * totalArray=[indeOfExtraInfo arrayByAddingObjectsFromArray:indeOfResultArray];
        
        self.searchIndexString=[totalArray componentsJoinedByString:@"\n"];
        
    }
    path  = [path stringByTrimmingCharactersInSet:[NSCharacterSet whitespaceAndNewlineCharacterSet]];//去掉两端空格和换行

//    NSRegularExpression *regular = [[NSRegularExpression alloc] initWithPattern:@"\\s+"
//                                                                        options:NSRegularExpressionCaseInsensitive
//                                                                          error:nil];
//    path = [regular stringByReplacingMatchesInString:path options:NSMatchingReportProgress  range:NSMakeRange(0, [path length]) withTemplate:@".*?"];
    
    BOOL isorBeginResults=[path hasPrefix:@"results>"];
    BOOL isorBeginExtraInfo=[path hasPrefix:@"extraInfo>"];
    if (!isorBeginExtraInfo&&!isorBeginResults) {
        path=[@"results>" stringByAppendingString:path];
    }

    
    
//    NSPredicate * predicate = [NSPredicate predicateWithFormat:@"SELF MATCHES (^|\s)(%@)\s+",path];

    NSRegularExpression * regualar2=[[NSRegularExpression alloc] initWithPattern:[NSString stringWithFormat:@"(^|\\s)(%@)\\s+",path] options:NSRegularExpressionCaseInsensitive error:nil];
    
    NSArray * matchResults=[regualar2 matchesInString:self.searchIndexString options:NSMatchingReportCompletion range:NSMakeRange(0, self.searchIndexString.length)];
    
    NSMutableArray * array=[[NSMutableArray alloc] init];
    for(NSTextCheckingResult *result in matchResults){
            NSString *str = [_searchIndexString substringWithRange:NSMakeRange(result.range.location,result.range.length)];
            [array addObject:[self find:str]];
    }
    
    return array;
}

-(BOOL)isModelType:(NSString *)modelType{
    
    return [modelType isEqualToString:self.modelType];
}


-(BOOL)isErrorCode:(NSInteger)errorCode{

    
    return errorCode==self.errorCode;
}

/**
 * 判断是否正确获得结果
 * @return boolean            是否正确获得
 */

-(BOOL)isResultsOK{

    
    return [self isErrorCode:0];

}
@end
