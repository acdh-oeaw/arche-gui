/**
* COUNT THE ROOTS
* we need to count the root ids before we run the bigger sql
**/
DROP FUNCTION gui.count_root_views_func();
CREATE FUNCTION gui.count_root_views_func()
  RETURNS table (id bigint)
AS $func$
DECLARE 
BEGIN	
/*count the root elements which type is TopCollection */
RETURN QUERY
    WITH root_count as (
	select COUNT(DISTINCT(r.id))
	from metadata as m
	left join relations as r on r.id = m.id
        left join resources as rs on rs.id = m.id 
	where
            m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
            and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#TopCollection'
            and rs.state = 'active'            
    ) select * from root_count;
END
$func$
LANGUAGE 'plpgsql';	

/*
* ROOT VIEW FUNCTION 
* generate the arche gui root view list
*/
DROP FUNCTION  gui.root_views_func(_lang text);
CREATE FUNCTION gui.root_views_func(_lang text DEFAULT 'en')
  RETURNS table (id bigint, title text, description text, avDate timestamp, acdhid text )
AS $func$
DECLARE 
    /* declare a second language variable, because if we dont have a value on the 
     * queried language then we are getting the results on the other language */
    _lang2 text := 'de';
    _lang3 text := 'und';
BEGIN
    /* check the languages and set up the language codes */
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
RETURN QUERY
WITH root_data as (
    select 
        DISTINCT(m.id) as id,
        COALESCE(
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang3 limit 1)
        ) as title,	
        COALESCE(
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang3 limit 1)
        ) as description,
        CAST((select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' LIMIT 1) as timestamp)as avdate,
        (select mv.value from metadata_view as mv where mv.id = m.id and mv.type = 'ID' and mv.value LIKE CAST('%/id.acdh.oeaw.ac.at/%' as varchar)  LIMIT 1) as acdhid
    from metadata_view as m
    left join resources as rs on rs.id = m.id
    where 
	m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
	and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#TopCollection'
	and rs.state = 'active'	
) 
select 
    rd.id, rd.title,  rd.description, rd.avdate, rd.acdhid
from root_data as rd;
END
$func$
LANGUAGE 'plpgsql';


/*
* DETAIL VIEW FUNCTION 
* get the detail view resource data by the resource identifier
* _identifier => full repo api url, f.e.: https://repo.hephaistos.arz.oeaw.ac.at/api/201064
* Because we supporting the 3rd party identifiers too, like vicav, etc
* execution time between: 140-171ms
*/
DROP FUNCTION IF EXISTS gui.detail_view_func(text, text);
CREATE FUNCTION gui.detail_view_func(_identifier text, _lang text DEFAULT 'en')
    RETURNS table (id bigint, property text, type text, value text, relvalue text, acdhid text, vocabsid text, accessRestriction text, language text )
    
AS $func$
DECLARE
    _lang2 text := 'de';
	_lang3 text := 'und';
    /* get the arche gui identifier */
    _main_id bigint := (select i.id from identifiers as i where i.ids =_identifier);
BEGIN
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
    /* get the basic metadata values */
    DROP TABLE IF EXISTS detail_meta;
    CREATE TEMPORARY TABLE detail_meta AS (
        WITH dmeta as (
            select mv.id, mv.property, mv.type, mv.value, mv.lang
            from metadata_view as mv 
            where mv.id = _main_id
        )
        select * from dmeta
    );
    --get the english values
    DROP TABLE IF EXISTS detail_meta_main_lng_en;
    CREATE TEMPORARY TABLE detail_meta_main_lng_en AS (
        WITH dmeta as (
            select DISTINCT(CAST(m.id as VARCHAR)), m.value, i.ids as acdhId, i2.ids as vocabsid, m.lang
            from metadata as m
            left join detail_meta as dm on CAST(dm.value as INT) = m.id and m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
            left join identifiers as i on i.id = m.id and i.ids LIKE CAST('%.acdh.oeaw.ac.at/api/%' as varchar)
            left join identifiers as i2 on i2.id = m.id and i2.ids LIKE CAST('%vocabs.acdh.oeaw.ac.at/%' as varchar)
            where dm.type = 'REL' and m.lang='en'
        )
        select * from dmeta
    );
    --get the german values
    DROP TABLE IF EXISTS detail_meta_main_lng_de;
    CREATE TEMPORARY TABLE detail_meta_main_lng_de AS (
        WITH dmeta as (
            select DISTINCT(CAST(m.id as VARCHAR)), m.value, i.ids as acdhId, i2.ids as vocabsid, m.lang
            from metadata as m
            left join detail_meta as dm on CAST(dm.value as INT) = m.id and m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
            left join identifiers as i on i.id = m.id and i.ids LIKE CAST('%.acdh.oeaw.ac.at/api/%' as varchar)
            left join identifiers as i2 on i2.id = m.id and i2.ids LIKE CAST('%vocabs.acdh.oeaw.ac.at/%' as varchar)
            where dm.type = 'REL' and m.lang='de'
        )
        select * from dmeta
    );
	
    DROP TABLE IF EXISTS detail_meta_main_lng_und;
    CREATE TEMPORARY TABLE detail_meta_main_lng_und AS (
        WITH dmeta as (
            select DISTINCT(CAST(m.id as VARCHAR)), m.value, i.ids as acdhId, i2.ids as vocabsid, m.lang
            from metadata as m
            left join detail_meta as dm on CAST(dm.value as INT) = m.id and m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
            left join identifiers as i on i.id = m.id and i.ids LIKE CAST('%.acdh.oeaw.ac.at/api/%' as varchar)
            left join identifiers as i2 on i2.id = m.id and i2.ids LIKE CAST('%vocabs.acdh.oeaw.ac.at/%' as varchar)
            where dm.type = 'REL' and m.lang='und'
        )
        select * from dmeta
    );
	
    -- compare the missing values and extend the missing labels
    IF _lang = 'en'
    THEN
        DROP TABLE IF EXISTS reldata;
        CREATE TEMPORARY TABLE reldata AS (
            WITH dmeta as (
                select t3.id, t3.value, t3.acdhid, t3.vocabsid, t3.lang  
                from detail_meta_main_lng_en as t3
                UNION
                select t1.id, t1.value, t1.acdhid, t1.vocabsid, _lang as lang
                from detail_meta_main_lng_de as t1 
                where 
                NOT EXISTS( 
                    select t2.id, t2.value, t2.acdhid, t2.vocabsid, _lang as lang
                    from detail_meta_main_lng_en as t2 
                    where t1.id = t2.id
                )
                UNION
                select und.id, und.value, und.acdhid, und.vocabsid, 'en' as lang
                from detail_meta_main_lng_und as und
                where				
                NOT EXISTS( 
                    select t2.id, t2.value, t2.acdhid, t2.vocabsid, 'en' as lang
                    from detail_meta_main_lng_en as t2 
                    where und.id = t2.id
                )
                order by id
            )
            select * from dmeta
        );
    END IF;

    IF _lang = 'de'
    THEN
        DROP TABLE IF EXISTS reldata;
        CREATE TEMPORARY TABLE reldata AS (
            WITH dmeta as (
                select t3.id, t3.value, t3.acdhid, t3.vocabsid, t3.lang  
                from detail_meta_main_lng_de as t3
                UNION
                select t1.id, t1.value, t1.acdhid, t1.vocabsid, _lang as lang
                from detail_meta_main_lng_en as t1
                where 
                NOT EXISTS( 
                    select t2.id, t2.value, t2.acdhid, t2.vocabsid, _lang as lang
                    from detail_meta_main_lng_de as t2 
                    where t1.id = t2.id
                )
                UNION
                select und.id, und.value, und.acdhid, und.vocabsid, 'de' as lang
                from detail_meta_main_lng_und as und
                where	
                NOT EXISTS( 
                    select t2.id, t2.value, t2.acdhid, t2.vocabsid, 'de' as lang
                    from detail_meta_main_lng_de as t2 
                    where und.id = t2.id
                )
                order by id
            )
            select * from dmeta
        );
    END IF;
	
    RETURN QUERY
    select dm.id, dm.property, dm.type, 
	dm.value, 
	dmr.value as relvalue, 
	dmr.acdhid,
	dmr.vocabsid,
	CASE WHEN dm.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' THEN dmr.value
	ELSE ''
	END,
	( CASE WHEN dm.lang IS NULL OR dm.lang = 'und' THEN _lang ELSE dm.lang end ) as language
    from detail_meta as dm
    left join reldata as dmr on dmr.id = dm.value
    left join resources as rs on rs.id = dm.id 
    where rs.state = 'active'      
    order by property; 
END
$func$
LANGUAGE 'plpgsql';


/*
* Generate the collection and child tree view data tree
* _pid -> root resource ID
*/
DROP FUNCTION gui.collection_views_func(text, text);
CREATE FUNCTION gui.collection_views_func(_pid text, _lang text DEFAULT 'en' )
    RETURNS table (mainid bigint, parentid bigint, title text, accesres text, license text, binarysize text, filename text, locationpath text, depth integer)
AS $func$
DECLARE
    _lang2 text := 'de';
BEGIN
/* generate the accessrestriction values*/
IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
DROP TABLE IF EXISTS accessres;
CREATE TEMP TABLE accessres AS (
    WITH acs as (
        select 
        distinct(r.target_id) as accessid , mv.value,
        mv.lang
        from relations as r
        left join metadata_view as mv on mv.id = r.target_id
        where r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
        and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
        and mv.lang = _lang
    ) select * from acs
);
/* get only the collection resource and the parent id  and also the depth to we can build up the tree view */
DROP TABLE IF EXISTS basic_collection_data;
CREATE TEMPORARY TABLE basic_collection_data(mainid bigint, parentid bigint, depth integer);
INSERT INTO basic_collection_data( 
    WITH RECURSIVE subordinates AS (
        SELECT
            mv.id as mainid,
            CAST(mv.value as bigint) as parentid, 
            1 as depthval
        FROM
            metadata_view as mv
        WHERE
            mv.value = _pid
            and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' 
        UNION
            SELECT
                mv2.id,
                CAST(mv2.value as bigint) as m2val, 
                depthval + 1 
            FROM
                metadata_view as mv2
            INNER JOIN subordinates s ON s.mainid = CAST(mv2.value as bigint) and  mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' 
    ) select * from subordinates
);

/* extend the tree data with the property data what we need to display on the gui */
DROP TABLE IF EXISTS collectionData;
CREATE TEMP TABLE collectionData(mainid bigint, parentid bigint, title text, accesres bigint, license text, binarysize text, filename text, locationpath text, depth integer);
INSERT INTO collectionData( 
    WITH  c2d AS (
        select 
            cd.mainid, cd.parentid, 
            (select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and id = cd.mainid limit 1) as title,
            (select CAST(value as bigint) from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and id = cd.mainid limit 1) as accessres,
            (select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLicense' and id = cd.mainid limit 1) as license,
            (select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasBinarySize' and id = cd.mainid limit 1) as binarysize,
            (select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasFilename' and id = cd.mainid limit 1) as filename,
            (select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLocationPath' and id = cd.mainid limit 1) as locationpath,
            cd.depth
        From basic_collection_data as cd
    ) select * from c2d	
);
/* return the results and change the accessrestriction id to the url*/
RETURN QUERY
    select 
        mv.mainid, mv.parentid, mv.title, ar.value, mv.license, mv.binarysize, mv.filename, mv.locationpath, mv.depth 
    from collectionData as mv
    left join accessres as ar on mv.accesres  = ar.accessid
    left join resources as rs on rs.id = mv.mainid 
    where rs.state = 'active'
    order by mv.depth;
END
$func$
LANGUAGE 'plpgsql';

/*
* Generate the GUI child list by the CHILD VIEW FUNCTION 
* _parentid = full url -> https://repo.hephaistos.arz.oeaw.ac.at/api/207984
* _limit = how many resource we want to display in the view
* _page = for paging, first page is 0
* _orderby = the ordering property -> https://vocabs.acdh.oeaw.ac.at/schema#hasTitle
* _lang = 'en' or 'de'
* select * from gui.child_views_func('https://arche-dev.acdh-dev.oeaw.ac.at/api/8145', '10', '0', 'desc', 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', 'en', ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' ])
*/
DROP FUNCTION gui.child_views_func(text, text, text, text, text, text, text[] );
CREATE FUNCTION gui.child_views_func(_parentid text, _limit text, _page text, _orderby text, _orderprop text, _lang text DEFAULT 'en',  _rdftype text[] DEFAULT '{}' )
    RETURNS table (id bigint, title text, avDate timestamp, description text, accesres text, titleimage text, acdhtype text, orderid integer)
AS $func$    
    DECLARE _lang2 text := 'de';
    DECLARE _lang3 text := 'und';
    /* we can just send string from the php so we need to cast the paging values to bigint */
    DECLARE limitint bigint := cast ( _limit as bigint);
    DECLARE pageint bigint := cast ( _page as bigint);
BEGIN
RAISE NOTICE USING MESSAGE = _orderby;
    /* set up the secondary language */
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
DROP TABLE IF EXISTS child_ids;
CASE WHEN _orderby = 'asc' then 
    CREATE TEMP TABLE child_ids AS(
    WITH ids AS (
        select 
            DISTINCT(r.id),
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = _orderprop and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = _orderprop and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = _orderprop limit 1)
            ) ordervalue,
            /* handle the language for title */
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
            ) as title,
            (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate,
            /* handle the language for the description */
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription'  limit 1)
            ) description,
            (select mv.value from relations as r2 left join metadata_view as mv on r2.target_id = mv.id where r.id = r2.id and r2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and
            mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang) as accessres,
            (select mv.value from metadata_view as mv where r.id = mv.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' limit 1) as titleimage,
            (select mv.value from metadata_view as mv where r.id = mv.id and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv.value like '%vocabs.%'  limit 1) as acdhtype
        from relations as r
        left join identifiers as i on i.id = r.target_id 
        where r.property = ANY (_rdftype)
            and i.ids = _parentid
        order by 
            ordervalue asc
            limit limitint
            offset pageint
    ) select * from ids		
);

ELSE
    CREATE TEMP TABLE child_ids AS(
    WITH ids AS (
        select 
            DISTINCT(r.id),
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = _orderprop and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = _orderprop and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = _orderprop limit 1)
            ) ordervalue,
            /* handle the language for title */
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
            ) as title,
            (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate,
            /* handle the language for the description */
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' limit 1)
            ) description,
            (select mv.value from relations as r2 left join metadata_view as mv on r2.target_id = mv.id where r.id = r2.id and r2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and
            mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang) as accessres,
            (select mv.value from metadata_view as mv where r.id = mv.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' limit 1) as titleimage,
            (select mv.value from metadata_view as mv where r.id = mv.id and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv.value like '%vocabs.%'  limit 1) as acdhtype
        from relations as r
        left join identifiers as i on i.id = r.target_id 
        where r.property = ANY (_rdftype)
            and i.ids = _parentid
        order by 
            ordervalue desc
            limit limitint
            offset pageint
    ) select * from ids		
);
END CASE;
alter table child_ids add orderid serial;
	
RETURN QUERY
    select DISTINCT(ci.id), ci.title, CAST(ci.avdate as timestamp), ci.description, ci.accessres, ci.titleimage, ci.acdhtype, ci.orderid
    from child_ids as ci 
    left join resources as rs on rs.id = ci.id 
    where rs.state = 'active'
    order by ci.orderid;
END
$func$
LANGUAGE 'plpgsql';

/*
* get the sum of the child gui view resources for the pager
* _parentid = full url -> https://repo.hephaistos.arz.oeaw.ac.at/api/207984
*/
DROP FUNCTION gui.child_sum_views_func(text, text[] );
CREATE FUNCTION gui.child_sum_views_func(_parentid text,  _rdftype text[] DEFAULT '{}')
    RETURNS table (countid bigint)
AS $func$

BEGIN
    DROP TABLE IF EXISTS child_ids;
    CREATE TEMP TABLE child_ids AS(
    WITH ids AS (
            select 
                DISTINCT(r.id)
            from relations as r
            left join identifiers as i on i.id = r.target_id
            left join resources as rs on rs.id = r.id
            where r.property = ANY (_rdftype)
            and rs.state = 'active'
            and i.ids = _parentid
    ) select * from ids		
);
	
RETURN QUERY
    select count(ci.id)
    from child_ids as ci
    left join resources as rs on rs.id = ci.id 
    where rs.state = 'active';
END
$func$
LANGUAGE 'plpgsql';

/*
* generate the data for the gui BREADCRUMB
* mainid -> simple int as text -> '207984'
*/
DROP FUNCTION gui.breadcrumb_view_func(text, text );
CREATE FUNCTION gui.breadcrumb_view_func(_pid text, _lang text DEFAULT 'en' )
    RETURNS table (mainid bigint, parentid bigint, parentTitle text, depth integer)
AS $func$
    DECLARE _lang2 text := 'de';
    DECLARE _lang3 text := 'und';
BEGIN
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
    DROP TABLE IF EXISTS breadcrumbdata;
    CREATE TEMPORARY TABLE breadcrumbdata(mainid bigint, parentid bigint, depth integer);
	INSERT INTO breadcrumbdata( 
            WITH RECURSIVE subordinates AS (
                SELECT
                    mv.id as mainid,
                    CAST(mv.value as bigint) as parentid,
                    1 as depthval
                FROM
                    metadata_view as mv
                WHERE
                    mv.id = CAST(_pid as bigint)
                    and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' 
                UNION
                SELECT
                    mv2.id,
                    CAST(mv2.value as bigint) as m2val,
                    depthval + 1 
                FROM
                    metadata_view as mv2
                INNER JOIN subordinates s ON s.parentid = mv2.id and  mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' 
            ) select * from subordinates
	);

RETURN QUERY
    select 
        DISTINCT(bd.mainid), bd.parentid, 
        COALESCE(
            (select mv.value from metadata_view as mv where mv.id = bd.parentid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
            (select mv.value from metadata_view as mv where mv.id = bd.parentid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
            (select mv.value from metadata_view as mv where mv.id = bd.parentid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang3 limit 1)
        ) as title,
        bd.depth 
    from 
        breadcrumbdata as bd
    left join resources as rs on rs.id = bd.mainid 
    where rs.state = 'active';
END
$func$
LANGUAGE 'plpgsql';


/**
* Get Members API SQL
* _repoid -> id of the root resource
*/
DROP FUNCTION gui.get_members_func(text, text);
CREATE FUNCTION gui.get_members_func(_repoid text, _lang text DEFAULT 'en')
  RETURNS table (id bigint, title text)
AS $func$
    DECLARE _lang2 text := 'de';
    DECLARE _lang3 text := 'und';
BEGIN
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
RETURN QUERY
    WITH subordinates AS (	
        select 
            mv.id,
            COALESCE(
                (select mv2.value from metadata_view as mv2 where mv.id = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1),	
                (select mv2.value from metadata_view as mv2 where mv.id = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1),
                (select mv2.value from metadata_view as mv2 where mv.id = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang3 limit 1)
            ) as title	
        from
        metadata_view as mv
        left join resources as rs on rs.id = mv.id
        where 
        mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isMemberOf'
        and mv.value = _repoid 
        and rs.state = 'active'
    ) select * from subordinates order by title;
END
$func$
LANGUAGE 'plpgsql';	


/**
* API CALLS
**/

/**
* API getDATA
**/
DROP FUNCTION gui.apiGetData(text, text);
CREATE FUNCTION gui.apiGetData(_class text, _searchStr text)
    RETURNS table (id bigint, property text, value text, lang text)
AS $func$

BEGIN
    DROP TABLE IF EXISTS ids;
    CREATE TEMPORARY TABLE ids(id bigint NOT NULL);
    INSERT INTO ids(
        select  
            mv.id
        from metadata_view as mv
        where
        mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
        and mv.value = _class 
    );

return query
    select 
        mv.id, mv.property, mv.value, mv.lang
    from ids as i 
    left join metadata_view as mv on mv.id = i.id
    left join resources as rs on rs.id = mv.id 
    where 
        mv.property in ('https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', 'https://vocabs.acdh.oeaw.ac.at/schema#hasAlternativeTitle' ) 
        and LOWER(mv.value) like '%' ||_searchStr || '%' 
        and rs.state = 'active';
END
$func$
LANGUAGE 'plpgsql';

/**
*  INVERSE TABLE SQL
**/
DROP FUNCTION gui.inverse_data_func(text, text);
CREATE FUNCTION gui.inverse_data_func(_identifier text, _lang text DEFAULT 'en')
  RETURNS table (id bigint, property text, title text)
AS $func$
DECLARE 

BEGIN
	
--get all inverse ids
DROP TABLE IF EXISTS  inverseIds;
CREATE TEMP TABLE inverseIds AS (
    select 
    DISTINCT(mv.id), mv.property 
    from metadata_view as mv
    where 
    mv.value = _identifier
    and mv.property NOT IN ('https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' , 'https://vocabs.acdh.oeaw.ac.at/schema#hasPid')
    and mv.type not in ('http://www.w3.org/2001/XMLSchema#integer', 'http://www.w3.org/2001/XMLSchema#long', 'http://www.w3.org/2001/XMLSchema#number',
					   'http://www.w3.org/2001/XMLSchema#decimal', 'http://www.w3.org/2001/XMLSchema#nonNegativeInteger')
);
RETURN QUERY
    select 
        DISTINCT(iv.id), iv.property, 
        COALESCE(
            (select mv2.value from metadata_view as mv2 where mv2.id = iv.id  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = 'en' LIMIT 1),	
            (select mv2.value from metadata_view as mv2 where mv2.id = iv.id  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = 'de' LIMIT 1),
            (select mv2.value from metadata_view as mv2 where mv2.id = iv.id  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = 'und' LIMIT 1)
        )
    from inverseIds as iv
    left join resources as rs on rs.id = iv.id
    where rs.state = 'active';
END
$func$
LANGUAGE 'plpgsql';



/**
* TOOLTIP ONTOLOGY SQL
**/
DROP FUNCTION gui.ontology_func(text);
CREATE FUNCTION gui.ontology_func(_lang text DEFAULT 'en')
  RETURNS table (id bigint, title text, description text, type text)
AS $func$
DECLARE 

BEGIN
    DROP TABLE IF EXISTS  ontologyData;
    CREATE TEMP TABLE ontologyData AS (
        select mv.id, 
        mv2.property, mv2.value, mv2.lang
        from metadata_view as mv
        left join metadata_view as mv2 on mv.id = mv2.id and mv.value in ('http://www.w3.org/2002/07/owl#DatatypeProperty', 'http://www.w3.org/2002/07/owl#ObjectProperty')
        where 
        mv2.property in ('http://www.w3.org/2000/01/rdf-schema#comment', 'http://www.w3.org/2004/02/skos/core#altLabel')
        and mv2.lang = _lang
        order by mv.id
    );
RETURN QUERY	
    select DISTINCT(od.id), 
    (select od3.value from ontologyData as od3 where od3.id = od.id and od3.property = 'http://www.w3.org/2004/02/skos/core#altLabel' limit 1) as title,
            (select od2.value from ontologyData as od2 where od2.id = od.id and od2.property = 'http://www.w3.org/2000/01/rdf-schema#comment' limit 1) as description,
    REPLACE(mv.value, 'https://vocabs.acdh.oeaw.ac.at/schema#', 'acdh:')
    from ontologyData as od
    left join metadata_view as mv on mv.id = od.id and mv.type = 'ID' and mv.value like 'https://vocabs.acdh%'
    left join resources as rs on rs.id = od.id 
    where rs.state = 'active';
END
$func$
LANGUAGE 'plpgsql';

/**
* COUNT THE binaries and main collections for the Ckeditor plugin
**/
DROP FUNCTION gui.count_binaries_collection_func();
CREATE FUNCTION gui.count_binaries_collection_func()
RETURNS table (collections bigint, binaries bigint)
AS $func$
DECLARE 
BEGIN

    DROP TABLE IF EXISTS count_binaries;
    CREATE TEMP TABLE count_binaries AS (
        WITH count_binaries as (
            select 
                COUNT(id) as id
            from metadata_view 
            where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasBinarySize'
            and CAST(value as bigint) > 0
        ) Select id from count_binaries
    );
DROP TABLE IF EXISTS count_main_collections;
CREATE TEMP TABLE count_main_collections AS (
    WITH count_main_collections as (
        select DISTINCT(r.id) as id
        from metadata as m
        left join relations as r on r.id = m.id
        where
            m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
            and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#TopCollection'
    ) select count(*) as id from count_main_collections
);
RETURN QUERY
select 
c.id as collections,
(select b.id as binaries FROM count_binaries as b) as binaries
FROM count_main_collections as c
left join resources as rs on rs.id = c.id 
where rs.state = 'active';
END
$func$
LANGUAGE 'plpgsql';


/**
* Gui Detail view https://redmine.acdh.oeaw.ac.at/issues/9184 -> Related Publications and Resources SQL
*
* _identifier = acdh id -> 3425
* _lang = 'en' / 'de'
**/
DROP FUNCTION gui.related_publications_resources_views_func(text, text);
CREATE FUNCTION gui.related_publications_resources_views_func(_identifier text, _lang text DEFAULT 'en')
  RETURNS table (id bigint, title text, relatedtype text, acdhtype text )
AS $func$
DECLARE 
    /* declare a second language variable, because if we dont have a value on the 
     * queried language then we are getting the results on the other language */
    _lang2 text := 'de';
	_lang3 text := 'und';
BEGIN
    /* check the languages and set up the language codes */
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
RETURN QUERY
WITH query_data as (
    select 
    DISTINCT(mv.id),
    COALESCE(
        (select mv2.value from metadata_view as mv2 where mv.id = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1),	
        (select mv2.value from metadata_view as mv2 where mv.id = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1),
        (select mv2.value from metadata_view as mv2 where mv.id = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang3 limit 1)
    ) as title,
    mv.property,
    (select mv2.value from metadata_view as mv2 where mv2.id = mv.id and mv2.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv2.value like '%.oeaw.ac.at/%' LIMIT 1)
    from metadata_view as mv
    left join resources as rs on rs.id = mv.id 
    where 
    mv.property in (
        'https://vocabs.acdh.oeaw.ac.at/schema#isDerivedPublication',		
        'https://vocabs.acdh.oeaw.ac.at/schema#isContinuedBy',
        'https://vocabs.acdh.oeaw.ac.at/schema#isDocumentedBy',
        'https://vocabs.acdh.oeaw.ac.at/schema#isSourceOf',
        'https://vocabs.acdh.oeaw.ac.at/schema#hasDerivedPublication',
        'https://vocabs.acdh.oeaw.ac.at/schema#relation',
        'https://vocabs.acdh.oeaw.ac.at/schema#continues',
        'https://vocabs.acdh.oeaw.ac.at/schema#documents',
        'https://vocabs.acdh.oeaw.ac.at/schema#hasSource'
    ) 
    and mv.value = _identifier
    and rs.state = 'active'
    UNION
    select 
    DISTINCT(CAST(mv.value as bigint)),
    COALESCE(
        (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1),	
        (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1),
        (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang3 limit 1)
    ) as title,
    mv.property,
    (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint) and mv2.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv2.value like '%.oeaw.ac.at/%' LIMIT 1)
    from metadata_view as mv
    where 
    mv.property IN ( 
        'https://vocabs.acdh.oeaw.ac.at/schema#hasDerivedPublication',
        'https://vocabs.acdh.oeaw.ac.at/schema#relation',
        'https://vocabs.acdh.oeaw.ac.at/schema#continues',
        'https://vocabs.acdh.oeaw.ac.at/schema#documents',
        'https://vocabs.acdh.oeaw.ac.at/schema#hasSource',
        'https://vocabs.acdh.oeaw.ac.at/schema#isDerivedPublication',		
        'https://vocabs.acdh.oeaw.ac.at/schema#isContinuedBy',
        'https://vocabs.acdh.oeaw.ac.at/schema#isDocumentedBy',
        'https://vocabs.acdh.oeaw.ac.at/schema#isSourceOf'
        )
    and mv.id = CAST(_identifier as bigint)
    order by title
) select * from query_data;
END
$func$
LANGUAGE 'plpgsql';

/**
* Get the repoid REL values HasTitle based on the defined language
**/
DROP FUNCTION gui.get_rel_values_func(text, text);
CREATE FUNCTION gui.get_rel_values_func(_identifier text, _lang text DEFAULT 'en')
  RETURNS table (id bigint, property text, title text, lang text )
AS $func$
DECLARE 
BEGIN
	
RETURN QUERY
WITH query_data as (
    select 
        CAST(mv.value as bigint) as propid, mv.property, mv2.value, mv2.lang
    from
    metadata_view as mv 
    left join metadata_view as mv2 on mv2.id = CAST(mv.value as bigint)
    where mv.id = CAST(_identifier as bigint) and mv.type = 'REL'
    and mv2.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang
    ) select * from query_data order by property;
END
$func$
LANGUAGE 'plpgsql';

/**
*  Arche Dashboard SQL
**/

/* PROPERTIES */
DROP FUNCTION gui.dash_properties_func();
CREATE FUNCTION gui.dash_properties_func()
  RETURNS table (property text, cnt bigint )
AS $func$
DECLARE 
BEGIN
	
RETURN QUERY
WITH query_data as (
    SELECT
	mv.property, count(mv.*) as cnt
    from public.metadata_view as mv
    where mv.property is not null
    GROUP BY mv.property
    UNION
    SELECT
	CASE WHEN mv.type = 'ID' THEN 'https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier' ELSE mv.property END as property, count(mv.*) as cnt	
    from public.metadata_view as mv
    where mv.property is null
    GROUP BY mv.property, mv.type
) select * from query_data order by property;
END
$func$
LANGUAGE 'plpgsql';

/* CLASSES */
DROP FUNCTION gui.dash_classes_func();
CREATE FUNCTION gui.dash_classes_func()
  RETURNS table (property text, cnt bigint )
AS $func$
DECLARE 
BEGIN
	
RETURN QUERY
WITH query_data as (
    select 
        mv.value as class, count(mv.*) as cnt
    from public.metadata_view as mv
    where mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
    group by mv.value
    ) select * from query_data order by class;
END
$func$
LANGUAGE 'plpgsql';

/* CLASSES PROPERTIES */
DROP FUNCTION gui.dash_classes_properties_func();
CREATE FUNCTION gui.dash_classes_properties_func()
  RETURNS table (class text, property text, cnt_distinct_value bigint, cnt bigint )
AS $func$
DECLARE 
BEGIN
	
RETURN QUERY
    WITH query_data as (
	select 
	t_class.value as class, tp.property, count(distinct tp.value) as cnt_distinct_value, count(*) as cnt
        from 
	(select mv.id, mv.value
            from public.metadata_view as mv
            where mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
	) t_class 
	inner join public.metadata_view tp on t_class.id =tp.id
        group by t_class.value, tp.property
    ) select * from query_data order by class;
END
$func$
LANGUAGE 'plpgsql';

/* TOPCOLLECTIONS */
DROP FUNCTION gui.dash_topcollections_func();
CREATE FUNCTION gui.dash_topcollections_func()
    RETURNS table (id bigint, title text, count_items bigint, max integer, sum_size_items numeric, bsize text )
AS $func$
DECLARE 
BEGIN
	
RETURN QUERY
WITH query_data as (
    SELECT 
	rootids.rootid id, 
	(select mv.value from metadata_view as mv where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.id = rootids.rootid limit 1) title,
	count(rel.id) count_items, 
	max(rel.n), 
	sum(CAST(m_rawsize.value as bigint) ) sum_size_items,
	(select mv.value from metadata_view as mv where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasBinarySize' and mv.id = rootids.rootid) bsize
	from 
	( select 
            DISTINCT(r.id) as rootid
            from metadata_view as m
            left join relations as r on r.id = m.id
            where
                m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#TopCollection'
                and r.id NOT IN ( 
                    SELECT DISTINCT(r.id) from metadata_view as m left join relations as r on r.id = m.id
                        where
                            m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                            and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
                            and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
                    )
	) as rootids, 
	public.get_relatives(rootid,'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf') rel
	left join metadata_view m_rawsize on m_rawsize.id = rel.id
	and m_rawsize.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasRawBinarySize'
	group by rootids.rootid, title
	) select * from query_data order by title;
END
$func$
LANGUAGE 'plpgsql';

/* FORMATS */
DROP FUNCTION gui.dash_formats_func();
CREATE FUNCTION gui.dash_formats_func()
  RETURNS table (format text, cnt_format bigint, cnt_size bigint, sum numeric )
AS $func$
DECLARE 
BEGIN
	
RETURN QUERY
WITH query_data as (
	select 
            mf.value as format, count(distinct mf.id) as cnt_format, count(ms.id) as cnt_size, sum(CAST(ms.value as bigint) )  
        from metadata_view mf
        join metadata_view ms on mf.id=ms.id
        where mf.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasFormat'
        and ms.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasRawBinarySize'
        group by mf.value
	) select * from query_data order by format;
END
$func$
LANGUAGE 'plpgsql';

/* Formats per Collection */
DROP FUNCTION gui.dash_formatspercollection_func();
CREATE FUNCTION gui.dash_formatspercollection_func()
  RETURNS table (id bigint, title text, type text, format text, count bigint, sum_size numeric )
AS $func$
DECLARE 
BEGIN
	
RETURN QUERY
WITH query_data as (
    SELECT 
    rootids.rootid id, 
    (select mv.value from metadata_view as mv where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.id = rootids.rootid limit 1) title,
    m_type.value as type, m_format.value as format, count(rel.id) as count, sum(CAST(m_size.value as bigint) ) as sum_size			
    from (
        select DISTINCT(r.id) as rootid
        from metadata_view as m
        left join relations as r on r.id = m.id
        where
            m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
            and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#TopCollection'
            and r.id NOT IN ( 
                SELECT 
                    DISTINCT(r.id) 
                from metadata_view as m 
                left join relations as r on r.id = m.id
                where
                    m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                    and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
                    and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
            )
        ) as rootids, public.get_relatives(rootid,'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf') rel
        left join metadata_view as m_format on m_format.id = rel.id
        and m_format.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasFormat'
        left join metadata_view as m_type on m_type.id = rel.id
        and m_type.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
        left join metadata_view as m_size on m_size.id = rel.id
        and m_size.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasRawBinarySize'
	group by rootids.rootid, title, m_format.value, m_type.value
    ) select * from query_data order by title;
END
$func$
LANGUAGE 'plpgsql';

/** GET Facet **/
DROP FUNCTION gui.dash_get_facet_func(text);
CREATE FUNCTION gui.dash_get_facet_func(_property text)
  RETURNS table (title text, type text, key text, cnt bigint )
AS $func$
DECLARE 
BEGIN
	
RETURN QUERY
WITH query_data as (
    SELECT 
        CASE 
            WHEN mv.type = 'REL' THEN 
                COALESCE(
                    (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint)  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = 'en' LIMIT 1),	
                    (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint)  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = 'de' LIMIT 1),
                    (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint)  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = 'und' LIMIT 1)
                )
            ELSE '' 
        END as title, 
        mv.type, mv.value as key, count(mv.*) as cnt
    FROM public.metadata_view as mv
    WHERE mv.property = _property
    GROUP BY mv.value, mv.type, title
    ) select * from query_data order by key;
END
$func$
LANGUAGE 'plpgsql';


/** New search test sql **/
/**
** select * from  gui.search_full_v3_func('*', ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#Person' ],  '%', 'en',  '10', '0',  'desc', 'title' )
*
* New search version  - for the new full_text_search DB without properties
* select * from  gui.search_full_v3_func('*', ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#Person' ],  '%', 'en',  '10', '0',  'desc', 'title' )
**/
DROP FUNCTION gui.search_full_v3_func(text, text[], text, text, text, text, text, text, bool);
CREATE FUNCTION gui.search_full_v3_func(_searchstr text DEFAULT '', _acdhtype text[] DEFAULT '{}', _acdhyears text DEFAULT '', _lang text DEFAULT 'en', _limit text DEFAULT '10', _page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'title', _binarySearch bool DEFAULT FALSE, _category text[] DEFAULT '{}' )
    RETURNS table (acdhid bigint, title text, description text, acdhtype text, headline_text text, headline_desc text, headline_binary text, avdate timestamp, accessres text, titleimage text, ids text, cnt bigint, pid text)
AS $func$
DECLARE	
    _lang2 text := 'de';
    _lang3 text := 'und';
    limitint bigint := cast ( _limit as bigint);
    pageint bigint := cast ( _page as bigint);
    _searchstr text := LOWER(_searchstr);
BEGIN
--remove the tables if they are exists
DROP TABLE IF EXISTS title_data;
DROP TABLE IF EXISTS type_data;
DROP TABLE IF EXISTS category_data;
DROP TABLE IF EXISTS years_data;
DROP TABLE IF EXISTS collection_data;

--create the dataset for the custom filter values
--DROP TYPE IF EXISTS dataset CASCADE;
IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'gui_fulltext_dataset')  THEN 
    CREATE TYPE gui_fulltext_dataset AS (acdhid bigint, headline_title text, headline_desc text, headline_binary text);
END IF;

--from php we can pass % so we need to remove then the years filter because then we dont filter years
CASE WHEN (_acdhyears <> '' and _acdhyears != '%') THEN 
    RAISE NOTICE USING MESSAGE = 'we have years string'; 
ELSE 
    _acdhyears = ''; 
    RAISE NOTICE USING MESSAGE = 'no years string'; 
END CASE;

CASE WHEN (_searchstr <> '' and _searchstr != '*') THEN         
    RAISE NOTICE USING MESSAGE = 'we have search string'; 
ELSE 
    _searchstr = ''; 
    RAISE NOTICE USING MESSAGE = 'no search string'; 
END CASE;

CREATE TEMPORARY TABLE collection_data of gui_fulltext_dataset;

--check the search strings in title/description and binary content
CASE 
    WHEN (_searchstr <> '' ) IS TRUE THEN
        RAISE NOTICE USING MESSAGE =  'search string query';
        CREATE TEMPORARY TABLE title_data of gui_fulltext_dataset;
        INSERT INTO title_data (acdhid, headline_title, headline_desc, headline_binary) select sd.id, sd.headline_title, sd.headline_desc, sd.headline_binary from gui.searchstrData_v3(_searchstr, _lang, _binarySearch) as sd order by sd.id; 
        INSERT INTO collection_data (acdhid, headline_title, headline_desc, headline_binary) SELECT td.acdhid, td.headline_title, td.headline_desc, td.headline_binary from title_data as td;
    ELSE
        RAISE NOTICE USING MESSAGE =  'search string query skipped';
END CASE;

--check the type
CASE 
    WHEN _acdhtype  = '{}' THEN
        --we dont have type definied so all type will be searchable.
        RAISE NOTICE USING MESSAGE =  'type query skipped';
    WHEN _acdhtype  != '{}' THEN
        -- we have type definied
        RAISE NOTICE USING MESSAGE =  'type query ';
        CASE 
            WHEN (select exists(select * from collection_data limit 1 ) ) IS TRUE THEN
                DROP TABLE IF EXISTS type_data;
                CREATE TEMPORARY TABLE type_data AS (
                    WITH type_data as (
                        SELECT 
                            DISTINCT(cd.acdhid) as id,                            
                            cd.headline_title,
                            cd.headline_desc,
                            cd.headline_binary
                        FROM collection_data as cd
                        LEFT JOIN metadata as m on m.id = cd.acdhid
                        LEFT JOIN full_text_search as fts on m.mid = fts.mid			
                        WHERE
                        (
                            m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                            and 
                            fts.raw  = ANY (_acdhtype)
                        ) limit 10000
                    ) select * from type_data
                );
		--remove the data which is not in the 
                DELETE FROM collection_data cd
                WHERE NOT EXISTS
                ( SELECT 1 FROM type_data td WHERE td.id = cd.acdhid );
			
            ELSE
                RAISE NOTICE USING MESSAGE =  'type query insert to collection data';
                CASE WHEN (_searchstr <> '' ) IS TRUE THEN
                    RAISE NOTICE USING MESSAGE =  'type query SKIPPED because we have search string but we dont have value!';
                ELSE
                    RAISE NOTICE USING MESSAGE =  'type query insert to collection data we dont have search string';
			
                    WITH type_data_temp as (
                        SELECT 
                            DISTINCT(m.id),
                            '' as headline_title,
                            '' as headline_desc,
                            '' as headline_binary
                        from full_text_search as fts
						LEFT JOIN metadata as m on m.mid = fts.mid
                        where
                        (
                            m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                            and 
                            fts.raw  = ANY (_acdhtype)
                        ) limit 10000
                    )INSERT INTO collection_data (acdhid, headline_title, headline_desc, headline_binary) SELECT td.id, td.headline_title, td.headline_desc, td.headline_binary from type_data_temp as td;
        END CASE;	
    END CASE;
END CASE;

--check the categories
CASE 
    WHEN _category  = '{}' THEN
        --we dont have type definied so all type will be searchable.
        RAISE NOTICE USING MESSAGE =  '_category query skipped';
    WHEN _category  != '{}' THEN
        -- we have type definied
        RAISE NOTICE USING MESSAGE =  '_category query ';
        CASE 
            WHEN (select exists(select * from collection_data limit 1 ) ) IS TRUE THEN
                DROP TABLE IF EXISTS category_data;
                CREATE TEMPORARY TABLE category_data AS (
                    WITH category_data as (
                        SELECT 
                            DISTINCT(cd.acdhid) as id,                            
                            cd.headline_title,
                            cd.headline_desc,
                            cd.headline_binary
                        FROM collection_data as cd
                        LEFT JOIN metadata_view as mv on mv.id = cd.acdhid			
                        WHERE
                        (
                            mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasCategory' 
                            and 
                            mv.value = ANY (_category)
                        ) limit 10000
                    ) select * from category_data
                );
		--remove the data which is not in the 
                DELETE FROM collection_data cd
                WHERE NOT EXISTS
                ( SELECT 1 FROM category_data td WHERE td.id = cd.acdhid );
			
            ELSE
                RAISE NOTICE USING MESSAGE =  'category query insert to collection data';
                CASE WHEN (_searchstr <> '' ) IS TRUE THEN
                    RAISE NOTICE USING MESSAGE =  'category query SKIPPED because we have search string but we dont have value!';
                ELSE
                    RAISE NOTICE USING MESSAGE =  'category query insert to collection data we dont have search string';
			
                    WITH category_data_temp as (
                        SELECT 
                            DISTINCT(mv.id),
                            '' as headline_title,
                            '' as headline_desc,
                            '' as headline_binary
                        from metadata_view as mv 
                        where
                        (
                            mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasCategory' 
                            and 
                            mv.value = ANY (_category)
                        ) limit 10000
                    )INSERT INTO collection_data (acdhid, headline_title, headline_desc, headline_binary) SELECT td.id, td.headline_title, td.headline_desc, td.headline_binary from category_data_temp as td;
        END CASE;	
    END CASE;
END CASE;

CASE 
    WHEN (_acdhyears <> '') IS TRUE THEN
        RAISE NOTICE USING MESSAGE =  'years query';
            CASE 
                WHEN (select exists(select * from collection_data limit 1 ) ) IS TRUE THEN
                    RAISE NOTICE USING MESSAGE =  'type query with have collection data';

                    CREATE TEMPORARY TABLE years_data AS (
                        WITH years_data as (
                            SELECT 
                                DISTINCT(cd.acdhid) as id,
                                cd.headline_title,
                                cd.headline_desc,
                                cd.headline_binary
                            FROM collection_data as cd
                            LEFT JOIN metadata as m on m.mid = cd.acdhid
                            WHERE
                            (
                                (m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
                                TO_CHAR(m.value_t, 'YYYY')  similar to _acdhyears   )
                            )	limit 10000
                        ) select * from years_data);

                    --delete the differences
                    DELETE FROM collection_data cd
                    WHERE NOT EXISTS
                    ( SELECT 1 FROM years_data yd WHERE yd.id = cd.acdhid );

		ELSE
                    RAISE NOTICE USING MESSAGE =  'years query insert to collection data';
		
                    CASE 
                        WHEN _acdhtype  != '{}' AND (_searchstr <> '' ) IS TRUE THEN
                            --we dont have type definied so all type will be searchable.
                            RAISE NOTICE USING MESSAGE =  'type query skipped';
			ELSE
                            WITH years_data_temp as (
                                SELECT 
                                    DISTINCT(m.id) as id,
                                    '' as headline_title,
                                    '' as headline_desc,
                                    '' as headline_binary
                                FROM metadata as m
                                WHERE
                                (
                                    (m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
                                    TO_CHAR(m.value_t, 'YYYY')  similar to _acdhyears  )
                                ) limit 10000
                            )INSERT INTO collection_data (acdhid, headline_title, headline_desc, headline_binary) SELECT  yd.id, yd.headline_title, yd.headline_desc, yd.headline_binary from years_data_temp as yd;

                END CASE;
        END CASE;	
    WHEN (_acdhyears <> '') IS NOT TRUE THEN
        RAISE NOTICE USING MESSAGE =  'years query skipped';
END CASE;

DROP TABLE IF EXISTS accessres;
CREATE TEMPORARY TABLE accessres AS (
    WITH accessres as (
        select 
            DISTINCT(mv.id), mv2.value, mv2.lang 
        from metadata_view as mv 
        left join metadata_view as mv2 on mv2.id = mv.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
        where mv.value like 'https://vocabs.acdh.oeaw.ac.at/archeaccessrestrictions/%'
    ) select * from accessres order by id
);

DROP TABLE IF EXISTS final_result;
CREATE TEMPORARY TABLE final_result AS (
    WITH final_result as (
        select 
            cd.acdhid, 
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang3 limit 1),
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
            ) as title,
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang3 limit 1),
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' limit 1)
            ) as description,
			 cd.headline_title, cd.headline_desc, cd.headline_binary,
            (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' limit 1) as acdhtype,
            (select CAST(mv.value as timestamp) as avdate from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate,	 
            COALESCE(
                (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = cd.acdhid and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang limit 1),	
                (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = cd.acdhid and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang2 limit 1),
                (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = cd.acdhid and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang3 limit 1),
                (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = cd.acdhid and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' limit 1)
            ) as accessres,
            (select mv.value from metadata_view as mv where mv.id = cd.acdhid  and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isTitleImageOf' limit 1) as titleimage,
            (select string_agg(ids.ids, ',') from identifiers as ids where ids.id = cd.acdhid) as ids
        from collection_data as cd
    )select * from final_result order by acdhid
);

DROP TABLE IF EXISTS count_data;
CREATE TEMPORARY TABLE count_data AS (
    select count(*) as cnt from final_result as cfd where cfd.title is not null
);

RETURN QUERY 
  select 
        fd.acdhid,  fd.title, fd.description, fd.acdhtype,  fd.headline_title, fd.headline_desc, fd.headline_binary,  CAST(fd.avdate as timestamp) as avdate, fd.accessres, fd.titleimage, fd.ids, (select cd.cnt from count_data as cd) as cnt,
        CASE WHEN (SELECT EXISTS (SELECT 1 FROM final_result WHERE lower(fd.ids) = '%hdl.handle%')) then
            (select mv.value from metadata_view as mv where mv.id = fd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasPid' limit 1)
        ELSE
            (select i.ids from identifiers as i  where i.id = fd.acdhid and i.ids like '%/id.acdh.oeaw.ac.at/%' limit 1)
        END as pid
    from final_result as fd
    left join resources as rs on rs.id = fd.acdhid
    where 
        fd.title is not null
        and rs.state = 'active'
    order by  
	CASE WHEN _orderby = 'desc' THEN
          CASE _orderby_prop
              -- Check for each possible value of sort_col.
              WHEN 'title' THEN fd.title
              WHEN 'type' THEN fd.acdhtype 
              WHEN 'avdate' THEN CAST(fd.avdate as text) 
              ELSE NULL
          END
      ELSE
          NULL
      END
      DESC,
	CASE WHEN _orderby = 'asc' THEN
          CASE _orderby_prop
              -- Check for each possible value of sort_col.
              WHEN 'title' THEN fd.title
              WHEN 'type' THEN fd.acdhtype 
              WHEN 'avdate' THEN CAST(fd.avdate as text) 
              ELSE NULL
          END
      ELSE
          NULL
      END
      ASC
	limit limitint
    offset pageint; 
END
$func$
LANGUAGE 'plpgsql';


/** NEW TITLE DESC BINARY SQL  - for the new full_text_search DB without properties**/
DROP FUNCTION gui.searchstrData_v3( text, text, bool);
CREATE FUNCTION gui.searchstrData_v3(_searchstr text DEFAULT '', _lang text DEFAULT 'en', _binarySearch bool DEFAULT FALSE )
  RETURNS table (id bigint, headline_title text, headline_desc text, headline_binary text)
AS $func$
DECLARE	
    _lang2 text := 'de';
    _lang3 text := 'und';   
    --_searchstr text := LOWER(REPLACE(REPLACE(_searchstr, 'https://', ''), 'http://', ''));
    _searchstr text := LOWER(_searchstr);
    
BEGIN

DROP TABLE IF EXISTS std_data;
DROP TABLE IF EXISTS sb_data;

CASE WHEN (_searchstr <> '' ) IS TRUE THEN
    CREATE TEMPORARY TABLE std_data AS (
        WITH std_data as (
            SELECT 
                DISTINCT COALESCE(m.id, fts.id) AS id,
                trim(regexp_replace(ts_headline('english', REGEXP_REPLACE(fts.raw, '\s', ' ', 'g'), to_tsquery(_searchstr), 'MaxFragments=3,MaxWords=15,MinWords=8'), '\s+', ' ', 'g')) as headline_title,
                '' as headline_desc,
                '' as headline_binary
            FROM full_text_search as fts 
            LEFT JOIN metadata as m on m.mid = fts.mid
            WHERE   
                (m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and websearch_to_tsquery('simple', _searchstr) @@ fts.segments )	
                
        UNION 
            SELECT 
                DISTINCT COALESCE(m.id, fts.id) AS id,
                '' as headline_title,
                trim(regexp_replace(ts_headline('english', REGEXP_REPLACE(fts.raw, '\s', ' ', 'g'), to_tsquery(_searchstr), 'MaxFragments=3,MaxWords=15,MinWords=8'), '\s+', ' ', 'g')) as headline_desc,
                '' as headline_binary
            FROM full_text_search as fts 
            LEFT JOIN metadata as m on m.mid = fts.mid
            WHERE  
                (m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and websearch_to_tsquery('simple', _searchstr) @@ fts.segments )
            limit 10000
            			
        ) select * from std_data
    );
    ELSE
	RAISE NOTICE USING MESSAGE =  'searchstring is empty';
END CASE;

CASE WHEN (_binarySearch IS TRUE) THEN
    CREATE TEMPORARY TABLE sb_data AS (
        WITH sb_data as (
            SELECT To_tsquery(_searchstr) AS query),
            ranked AS(
                SELECT DISTINCT(fts.id), fts.raw--, ts_rank_cd(segments,query) AS rank
                from  full_text_search as fts, sb_data
                where sb_data.query @@ segments and fts.mid is null
                limit 150
            )
            select ranked.id,
            '' as headline_title,
            '' as headline_desc,
            trim(regexp_replace(ts_headline(ranked.raw, sb_data.query, 'MaxFragments=3,MaxWords=15,MinWords=8'), '\s+', ' ', 'g')) as headline_binary
            from ranked, sb_data
            limit 150
    );	
ELSE
    RAISE NOTICE USING MESSAGE =  'there is no binary search option';
END CASE;

DROP TABLE IF EXISTS final_string_result;

if (SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE lower(table_name) = 'sb_data')) then
    CREATE TEMPORARY TABLE final_string_result AS (
        select * from std_data as std 
        UNION
        select * from sb_data as sb
    );
ELSE
    CREATE TEMPORARY TABLE final_string_result AS (
        select * from std_data as std 
    );
END IF;

RETURN QUERY 
    SELECT T1.id, MAX(T1.headline_title) AS headline_title, MAX(T1.headline_desc) As headline_desc, MAX(T1.headline_binary) As headline_binary 
    FROM final_string_result AS T1
    JOIN final_string_result AS T2 ON T1.id = T2.id
    left join resources as rs on rs.id = T1.id 
    where rs.state = 'active'
    GROUP BY T1.id;
END
$func$
LANGUAGE 'plpgsql';


/**
* OLD full text search SQL
**/

DROP FUNCTION gui.search_full_v2_func(text, text[], text, text, text, text, text, text, bool);
CREATE FUNCTION gui.search_full_v2_func(_searchstr text DEFAULT '', _acdhtype text[] DEFAULT '{}', _acdhyears text DEFAULT '', _lang text DEFAULT 'en', _limit text DEFAULT '10', _page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'title', _binarySearch bool DEFAULT FALSE )
    RETURNS table (acdhid bigint, title text, description text, acdhtype text, headline_text text, headline_desc text, headline_binary text, avdate timestamp, accessres text, titleimage text, ids text, cnt bigint)
AS $func$
DECLARE	
    _lang2 text := 'de';
    _lang3 text := 'und';
    limitint bigint := cast ( _limit as bigint);
    pageint bigint := cast ( _page as bigint);
    _searchstr text := LOWER(_searchstr);
BEGIN
--remove the tables if they are exists
DROP TABLE IF EXISTS title_data;
DROP TABLE IF EXISTS type_data;
DROP TABLE IF EXISTS years_data;
DROP TABLE IF EXISTS collection_data;

--create the dataset for the custom filter values
--DROP TYPE IF EXISTS dataset CASCADE;
IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'gui_fulltext_dataset')  THEN 
    CREATE TYPE gui_fulltext_dataset AS (acdhid bigint, headline_title text, headline_desc text, headline_binary text);
END IF;

--from php we can pass % so we need to remove then the years filter because then we dont filter years
CASE WHEN (_acdhyears <> '' and _acdhyears != '%') THEN 
    RAISE NOTICE USING MESSAGE = 'we have years string'; 
ELSE 
    _acdhyears = ''; 
    RAISE NOTICE USING MESSAGE = 'no years string'; 
END CASE;

CASE WHEN (_searchstr <> '' and _searchstr != '*') THEN         
    RAISE NOTICE USING MESSAGE = 'we have search string'; 
ELSE 
    _searchstr = ''; 
    RAISE NOTICE USING MESSAGE = 'no search string'; 
END CASE;

CREATE TEMPORARY TABLE collection_data of gui_fulltext_dataset;

--check the search strings in title/description and binary content
CASE 
    WHEN (_searchstr <> '' ) IS TRUE THEN
        RAISE NOTICE USING MESSAGE =  'search string query';
        CREATE TEMPORARY TABLE title_data of gui_fulltext_dataset;
        INSERT INTO title_data (acdhid, headline_title, headline_desc, headline_binary) select sd.id, sd.headline_title, sd.headline_desc, sd.headline_binary from gui.searchstrData(_searchstr, _lang, _binarySearch) as sd order by sd.id; 
        INSERT INTO collection_data (acdhid, headline_title, headline_desc, headline_binary) SELECT td.acdhid, td.headline_title, td.headline_desc, td.headline_binary from title_data as td;
    ELSE
        RAISE NOTICE USING MESSAGE =  'search string query skipped';
END CASE;

--check the type
CASE 
    WHEN _acdhtype  = '{}' THEN
        --we dont have type definied so all type will be searchable.
        RAISE NOTICE USING MESSAGE =  'type query skipped';
    WHEN _acdhtype  != '{}' THEN
        -- we have type definied
        RAISE NOTICE USING MESSAGE =  'type query ';
        CASE 
            WHEN (select exists(select * from collection_data limit 1 ) ) IS TRUE THEN
                DROP TABLE IF EXISTS type_data;
                CREATE TEMPORARY TABLE type_data AS (
                    WITH type_data as (
                        SELECT 
                            DISTINCT(fts.id),                            
                            cd.headline_title,
                            cd.headline_desc,
                            cd.headline_binary
                        FROM collection_data as cd
                        LEFT JOIN full_text_search as fts on cd.acdhid = fts.id
                        WHERE
                        (
                            fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                            and 
                            fts.raw  = ANY (_acdhtype)
                        ) limit 10000
                    ) select * from type_data
                );
		--remove the data which is not in the 
                DELETE FROM collection_data cd
                WHERE NOT EXISTS
                ( SELECT 1 FROM type_data td WHERE td.id = cd.acdhid );
			
            ELSE
                RAISE NOTICE USING MESSAGE =  'type query insert to collection data';
                CASE WHEN (_searchstr <> '' ) IS TRUE THEN
                    RAISE NOTICE USING MESSAGE =  'type query SKIPPED because we have search string but we dont have value!';
                ELSE
                    RAISE NOTICE USING MESSAGE =  'type query insert to collection data we dont have search string';
			
                    WITH type_data_temp as (
                        SELECT 
                            DISTINCT(fts.id),
                            '' as headline_title,
                            '' as headline_desc,
                            '' as headline_binary
                        from full_text_search as fts
                        where
                        (
                            fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                            and 
                            fts.raw  = ANY (_acdhtype)
                        ) limit 10000
                    )INSERT INTO collection_data (acdhid, headline_title, headline_desc, headline_binary) SELECT td.id, td.headline_title, td.headline_desc, td.headline_binary from type_data_temp as td;
        END CASE;	
    END CASE;
END CASE;

CASE 
    WHEN (_acdhyears <> '') IS TRUE THEN
        RAISE NOTICE USING MESSAGE =  'years query';
            CASE 
                WHEN (select exists(select * from collection_data limit 1 ) ) IS TRUE THEN
                    RAISE NOTICE USING MESSAGE =  'type query with have collection data';

                    CREATE TEMPORARY TABLE years_data AS (
                        WITH years_data as (
                            SELECT 
                                DISTINCT(fts.id),
                                cd.headline_title,
                                cd.headline_desc,
                                cd.headline_binary
                            FROM collection_data as cd
                            LEFT JOIN full_text_search as fts on fts.id = cd.acdhid				
                            WHERE
                            (
                                (fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
                                TO_CHAR(TO_TIMESTAMP(fts.raw, 'YYYY'), 'YYYY')  similar to _acdhyears  )
                            )	limit 10000
                        ) select * from years_data);

                    --delete the differences
                    DELETE FROM collection_data cd
                    WHERE NOT EXISTS
                    ( SELECT 1 FROM years_data yd WHERE yd.id = cd.acdhid );

		ELSE
                    RAISE NOTICE USING MESSAGE =  'years query insert to collection data';
		
                    CASE 
                        WHEN _acdhtype  != '{}' AND (_searchstr <> '' ) IS TRUE THEN
                            --we dont have type definied so all type will be searchable.
                            RAISE NOTICE USING MESSAGE =  'type query skipped';
			ELSE
                            WITH years_data_temp as (
                                SELECT 
                                    DISTINCT(fts.id),
                                    '' as headline_title,
                                    '' as headline_desc,
                                    '' as headline_binary
                                FROM full_text_search as fts
                                WHERE
                                (
                                    (fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
                                    TO_CHAR(TO_TIMESTAMP(fts.raw, 'YYYY'), 'YYYY')  similar to _acdhyears  )
                                ) limit 10000
                            )INSERT INTO collection_data (acdhid, headline_title, headline_desc, headline_binary) SELECT  yd.id, yd.headline_title, yd.headline_desc, yd.headline_binary from years_data_temp as yd;

                END CASE;
        END CASE;	
    WHEN (_acdhyears <> '') IS NOT TRUE THEN
        RAISE NOTICE USING MESSAGE =  'years query skipped';
END CASE;

DROP TABLE IF EXISTS accessres;
CREATE TEMPORARY TABLE accessres AS (
    WITH accessres as (
        select 
            DISTINCT(mv.id), mv2.value, mv2.lang 
        from metadata_view as mv 
        left join metadata_view as mv2 on mv2.id = mv.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
        where mv.value like 'https://vocabs.acdh.oeaw.ac.at/archeaccessrestrictions/%'
    ) select * from accessres order by id
);

DROP TABLE IF EXISTS final_result;
CREATE TEMPORARY TABLE final_result AS (
    WITH final_result as (
        select 
            cd.acdhid, 
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang3 limit 1),
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
            ) as title,
            COALESCE(
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang3 limit 1),
                (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' limit 1)
            ) as description,
			 cd.headline_title, cd.headline_desc, cd.headline_binary,
            (select mv.value from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' limit 1) as acdhtype,
            (select CAST(mv.value as timestamp) as avdate from metadata_view as mv where mv.id = cd.acdhid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate,	 
            COALESCE(
                (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = cd.acdhid and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang limit 1),	
                (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = cd.acdhid and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang2 limit 1),
                (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = cd.acdhid and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang3 limit 1),
                (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = cd.acdhid and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' limit 1)
            ) as accessres,
            (select mv.value from metadata_view as mv where mv.id = cd.acdhid  and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isTitleImageOf' limit 1) as titleimage,
            (select string_agg(ids.ids, ',') from identifiers as ids where ids.id = cd.acdhid) as ids
        from collection_data as cd
    )select * from final_result order by acdhid
);

DROP TABLE IF EXISTS count_data;
CREATE TEMPORARY TABLE count_data AS (
    select count(*) as cnt from final_result as cfd where cfd.title is not null
);

RETURN QUERY 
  select 
        fd.acdhid,  fd.title, fd.description, fd.acdhtype,  fd.headline_title, fd.headline_desc, fd.headline_binary,  CAST(fd.avdate as timestamp) as avdate, fd.accessres, fd.titleimage, fd.ids, (select cd.cnt from count_data as cd) as cnt
    from final_result as fd
    left join resources as rs on rs.id = fd.acdhid
    where 
        fd.title is not null
        and rs.state = 'active'
    order by  
	CASE WHEN _orderby = 'desc' THEN
          CASE _orderby_prop
              -- Check for each possible value of sort_col.
              WHEN 'title' THEN fd.title
              WHEN 'type' THEN fd.acdhtype 
              WHEN 'avdate' THEN CAST(fd.avdate as text) 
              ELSE NULL
          END
      ELSE
          NULL
      END
      DESC,
	CASE WHEN _orderby = 'asc' THEN
          CASE _orderby_prop
              -- Check for each possible value of sort_col.
              WHEN 'title' THEN fd.title
              WHEN 'type' THEN fd.acdhtype 
              WHEN 'avdate' THEN CAST(fd.avdate as text) 
              ELSE NULL
          END
      ELSE
          NULL
      END
      ASC
	limit limitint
    offset pageint; 
END
$func$
LANGUAGE 'plpgsql';


/** NEW TITLE DESC BINARY SQL **/

DROP FUNCTION gui.searchstrData( text, text, bool);
CREATE FUNCTION gui.searchstrData(_searchstr text DEFAULT '', _lang text DEFAULT 'en', _binarySearch bool DEFAULT FALSE )
  RETURNS table (id bigint, headline_title text, headline_desc text, headline_binary text)
AS $func$
DECLARE	
    _lang2 text := 'de';
    _lang3 text := 'und';   
    --_searchstr text := LOWER(REPLACE(REPLACE(_searchstr, 'https://', ''), 'http://', ''));
    _searchstr text := LOWER(_searchstr);
    
BEGIN

DROP TABLE IF EXISTS std_data;
DROP TABLE IF EXISTS sb_data;

CASE WHEN (_searchstr <> '' ) IS TRUE THEN
    CREATE TEMPORARY TABLE std_data AS (
        WITH std_data as (
            SELECT 
                DISTINCT(fts.id),
                trim(regexp_replace(ts_headline('english', REGEXP_REPLACE(fts.raw, '\s', ' ', 'g'), to_tsquery(_searchstr), 'MaxFragments=3,MaxWords=15,MinWords=8'), '\s+', ' ', 'g')) as headline_title,
                '' as headline_desc,
                '' as headline_binary
            FROM full_text_search as fts 
            WHERE   
                (fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and websearch_to_tsquery('simple', _searchstr) @@ fts.segments )	
                
        UNION 
            SELECT 
                DISTINCT(fts.id),
                '' as headline_title,
                trim(regexp_replace(ts_headline('english', REGEXP_REPLACE(fts.raw, '\s', ' ', 'g'), to_tsquery(_searchstr), 'MaxFragments=3,MaxWords=15,MinWords=8'), '\s+', ' ', 'g')) as headline_desc,
                '' as headline_binary
            FROM full_text_search as fts 
            WHERE  
                (fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and websearch_to_tsquery('simple', _searchstr) @@ fts.segments )
            limit 10000
            			
        ) select * from std_data
    );
    ELSE
	RAISE NOTICE USING MESSAGE =  'searchstring is empty';
END CASE;

CASE WHEN (_binarySearch IS TRUE) THEN
    CREATE TEMPORARY TABLE sb_data AS (
        WITH sb_data as (
        /*    SELECT 
                DISTINCT(fts.id),
                '' as headline_title,
                '' as headline_desc,
                CASE WHEN fts.property = 'BINARY' THEN
                    trim(regexp_replace(ts_headline('english', REGEXP_REPLACE(fts.raw, '\s', ' ', 'g'), to_tsquery(_searchstr), 'MaxFragments=3,MaxWords=15,MinWords=8'), '\s+', ' ', 'g'))
                ELSE '' 
                END as headline_binary
            FROM full_text_search as fts 
            WHERE
                 (fts.property = 'BINARY' and websearch_to_tsquery('simple', _searchstr) @@ fts.segments )
            limit 10000	*/
            SELECT To_tsquery(_searchstr) AS query),
            ranked AS(
                    SELECT DISTINCT(fts.id), fts.raw--, ts_rank_cd(segments,query) AS rank
                    from  full_text_search as fts, sb_data
                    where sb_data.query @@ segments
                    limit 150
                    )
            --select * from sb_data
            select ranked.id,
            '' as headline_title,
            '' as headline_desc,
            trim(regexp_replace(ts_headline(ranked.raw, sb_data.query, 'MaxFragments=3,MaxWords=15,MinWords=8'), '\s+', ' ', 'g')) as headline_binary
            --ts_headline(raw,q.query, 'MaxFragments=3,MaxWords=10,MinWords=4') as highlighted
            from ranked, sb_data
            limit 150
    );	
ELSE
    RAISE NOTICE USING MESSAGE =  'there is no binary search option';
END CASE;

DROP TABLE IF EXISTS final_string_result;

if (SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE lower(table_name) = 'sb_data')) then
    CREATE TEMPORARY TABLE final_string_result AS (
        select * from std_data as std 
        UNION
        select * from sb_data as sb
    );
ELSE
    CREATE TEMPORARY TABLE final_string_result AS (
        select * from std_data as std 
    );
END IF;

RETURN QUERY 
    SELECT T1.id, MAX(T1.headline_title) AS headline_title, MAX(T1.headline_desc) As headline_desc, MAX(T1.headline_binary) As headline_binary 
    FROM final_string_result AS T1
    JOIN final_string_result AS T2 ON T1.id = T2.id
    left join resources as rs on rs.id = T1.id 
    where rs.state = 'active'
    GROUP BY T1.id;
END
$func$
LANGUAGE 'plpgsql';


/**
* NEW LAZY LOAD tree view sql
*/
DROP FUNCTION IF EXISTS gui.collection_v2_views_func(text, text);
CREATE FUNCTION gui.collection_v2_views_func(_pid text, _lang text DEFAULT 'en' )
    RETURNS table (id bigint, title text, accesres text, license text, binarysize text, filename text, locationpath text)
AS $func$
DECLARE
    _lang2 text := 'de';
BEGIN
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
    DROP TABLE IF EXISTS accessres;
    CREATE TEMP TABLE accessres AS (
        WITH acs as (
            select 
                distinct(r.target_id) as accessid , mv.value,
                mv.lang
            from relations as r
            left join metadata_view as mv on mv.id = r.target_id
            where r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
                and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
                and mv.lang = _lang
        ) select * from acs
    );
	
    DROP TABLE IF EXISTS basic_collection_data;
    CREATE TEMPORARY TABLE basic_collection_data(id bigint);
    INSERT INTO basic_collection_data( 
        WITH RECURSIVE subordinates AS (
            select 
                mv.id
            from metadata_view as mv
            where
                mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
                and mv.value = _pid
            order by mv.property
        ) select * from subordinates
    );
	
    DROP TABLE IF EXISTS collectionData;
    CREATE TEMP TABLE collectionData(id bigint, title text, accesres bigint, license text, binarysize text, filename text, locationpath text);
    INSERT INTO collectionData( 
        WITH  c2d AS (
            select 
                bcd.id,
                (select mv.value from metadata_view as mv where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.id = bcd.id limit 1) as title,
                (select CAST(mv.value as bigint) from metadata_view as mv where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv.id = bcd.id limit 1) as accessres,
                (select mv.value from metadata_view as mv where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLicense' and mv.id = bcd.id limit 1) as license,
                (select mv.value from metadata_view as mv where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasBinarySize' and mv.id = bcd.id limit 1) as binarysize,
                (select mv.value from metadata_view as mv where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasFilename' and mv.id = bcd.id limit 1) as filename,
                (select mv.value from metadata_view as mv where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLocationPath' and mv.id = bcd.id limit 1) as locationpath
            from basic_collection_data as bcd
        ) select * from c2d	
    );

RETURN QUERY   
    select 
        mv.id, mv.title, ar.value, mv.license, mv.binarysize, mv.filename, mv.locationpath
    from collectionData as mv
    left join accessres as ar on mv.accesres  = ar.accessid
    left join resources as rs on rs.id = mv.id 
    where rs.state = 'active';
END
$func$
LANGUAGE 'plpgsql';


/**
* Versions block SQL
**/

DROP FUNCTION IF EXISTS gui.getResourceVersion( text, text);
CREATE FUNCTION gui.getResourceVersion(_identifier text, _lang text DEFAULT 'en')
  RETURNS table (id bigint, title text, avDate timestamp)
AS $func$
DECLARE	
    _lang2 text := 'de';
    _lang3 text := 'und';   
BEGIN
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
RETURN QUERY
    WITH RECURSIVE child_subordinates AS (
        SELECT
            mv.value
        FROM
            metadata_view as mv
        WHERE
            mv.id = CAST(_identifier as bigint)
            and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
        UNION
        SELECT
            mv2.value
        FROM
            metadata_view mv2
        INNER JOIN child_subordinates s ON CAST(s.value as bigint) = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
    ),
    --get the parents
    parent_subordinates AS (
        SELECT
            mv.id
        FROM
            metadata_view as mv
        WHERE
            mv.value = _identifier
            and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
        UNION
            SELECT
                    mv2.id
            FROM
                    metadata_view mv2
            INNER JOIN parent_subordinates s ON s.id = CAST(mv2.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
    ) SELECT
        CAST(c.value as bigint),
        COALESCE(
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(c.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1),	
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(c.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1),
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(c.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang3 limit 1)
        ) as title,
        (select CAST(mv2.value as timestamp) from metadata_view as mv2 where  mv2.id = CAST(c.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'  limit 1) as avdate
    FROM
        child_subordinates as c
    UNION
    Select 
        p.id,
        COALESCE(
            (select mv2.value from metadata_view as mv2 where mv2.id = p.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1),	
            (select mv2.value from metadata_view as mv2 where mv2.id = p.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1),
            (select mv2.value from metadata_view as mv2 where mv2.id = p.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang3 limit 1)
        ) as title,
        (select CAST(mv2.value as timestamp) from metadata_view as mv2 where  mv2.id = p.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'  limit 1) as avdate
    from parent_subordinates as p
    UNION
    select
        CAST(_identifier as bigint) as id,
        COALESCE(
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(_identifier as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1),	
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(_identifier as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1),
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(_identifier as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang3 limit 1)
        ) as title,
        (select CAST(mv2.value as timestamp) from metadata_view as mv2 where  mv2.id = CAST(_identifier as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'  limit 1) as avdate
    order by avdate desc;
END
$func$
LANGUAGE 'plpgsql';
