import pymysql,time,random
def connect():
    connection = pymysql.connect(host='127.0.0.1',
                             user='root',
                             password='',
                             db='jiugongzi',
                             charset='utf8mb4',
                             cursorclass=pymysql.cursors.DictCursor)
    cursor=connection.cursor()
    return cursor,connection
