# Feather Cache

##### CACHE DB Table SCHEMA


```
CREATE TABLE cache (
    id int primary key auto_increment,
    cache_key varchar(255) unique not null,
    cache_data mediumtext,
    expire_at int unsigned not null,
    created_at datetime not null default current_timestamp,
    updated_at datetime not null default current_timestamp on update current_timestamp,
    KEY cache_expire_at_dx (expire_at)
);
```
