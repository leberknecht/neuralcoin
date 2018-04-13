select
  symbol_id,
  avg(price),
  convert((min(trade.time) div 500) * 500 + 230, time) as time
from trade where symbol_id in (8,9) and
trade.time BETWEEN DATE_SUB(NOW(), INTERVAL 4 HOUR) and NOW()
group by trade.time div 500 ,symbol_id # 500 = 5 minutes
order by created_at desc limit 100;

#https://stackoverflow.com/questions/12345679/average-of-data-for-every-5-minutes-in-the-given-times

select
  symbol_id,
  avg(price),
  #convert((min(trade.time) div 30)*30, datetime) + INTERVAL 5 minute as endOfInterval

  convert((min(trade.time) div 30) * 30 + 15, time) as time
from trade where symbol_id in (8,9) and
                 trade.time BETWEEN DATE_SUB(NOW(), INTERVAL 4 HOUR) and NOW()
group by trade.time div 30 ,symbol_id # 500 = 5 minutes
order by created_at desc limit 100;


select
  avg(price),
  convert((min(trade.time) div 30)*30, datetime) + INTERVAL 30 SECOND as endOfInterval
  #convert((min(trade.time) div 30) * 30 + 15, time) as time
from trade where symbol_id in (8) and
                 trade.time BETWEEN DATE_SUB(NOW(), INTERVAL 4 HOUR) and NOW()
group by trade.time div 30
order by created_at desc limit 100;


select
  avg(price),
  #(min(trade.time) div 50)*50
  convert((min(trade.time) div 100)*100, datetime) + INTERVAL 1 minute as endOfInterval
from trade where
  trade.time BETWEEN DATE_SUB(NOW(), INTERVAL 2 HOUR) and NOW()
group by trade.time div 100;

select
  avg(price),
  #(min(trade.time) div 50)*50
  convert((min(trade.time) div 100)*100, datetime) + INTERVAL 1 minute as endOfInterval
from trade where
  trade.time BETWEEN DATE_SUB(NOW(), INTERVAL 2 HOUR) and NOW()

GROUP BY UNIX_TIMESTAMP(trade.time) DIV 30

# better:
select
  avg(price),
  from_unixtime(unix_timestamp(min(trade.time)) - mod(unix_timestamp(min(trade.time)), 30)),
  now()
from trade where symbol_id in (7) and
                 trade.time BETWEEN DATE_SUB( (NOW() - MOD(NOW(), 30)), INTERVAL 120 second) and (NOW() - MOD(NOW(), 30))
group by UNIX_TIMESTAMP(trade.time) div 30
order by created_at desc limit 100;




##
#
#
set @timeScope = (60 * 60);
set @myInterval = 20;
SELECT
  DATE_SUB(
      from_unixtime(unix_timestamp(NOW()) - mod(unix_timestamp(NOW()), @myInterval)),
      INTERVAL @myInterval * `i` second
  )
FROM (
  SELECT @row := @row + 1 as i
  FROM trade t, (SELECT @row := 0) r
  limit @timeScope / @myInterval
)
ORDER BY i ASC;

SELECT 10*n1.num + n2.num AS i
FROM numbers n1 CROSS JOIN numbers n2) nums
WHERE i <= @timeScope / @myInterval)
ORDER BY i ASC;

select n1.num, n2.num as i, n1.num * n2.num as inde from numbers n1 CROSS JOIN  numbers n2
order by i asc limit 100;


SELECT @row := @row + 1 as row
FROM trade t, (SELECT @row := 0) r
limit 25