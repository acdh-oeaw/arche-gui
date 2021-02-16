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
/*count the root elements which type is collection and doesnt have an ispartof property */
RETURN QUERY
    WITH root_count as (
	select COUNT(DISTINCT(r.id))
	from metadata as m
	left join relations as r on r.id = m.id
	where
            m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
            and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#TopCollection'            
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
  RETURNS table (id bigint, title text, titleimage text, description text, avDate timestamp, accesres text, acdhid text )
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
    select DISTINCT(r.id) as id,
        /* check the title based on the language*/	
	COALESCE(
		(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
		(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
		(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang3 limit 1)
	) as title,
	(select md.value from metadata_view as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' LIMIT 1) as titleImage,
        /* check the description based on the language*/
	COALESCE(
		(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
		(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
		(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang3 limit 1)
	) as description,
	CAST((select md.value from metadata as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' LIMIT 1) as timestamp)as avdate
	from metadata as m
	left join relations as r on r.id = m.id
	where
		m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
		and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#TopCollection'		
) select 
rd.id, rd.title, rd.titleimage, rd.description, rd.avdate,
(CASE WHEN 
        (select md.value from metadata_view as md where md.id = r.target_id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and md.lang = _lang LIMIT 1) IS NULL
    THEN
        (select md.value from metadata_view as md where md.id = r.target_id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and md.lang = _lang2 LIMIT 1)
    ELSE
        (select md.value from metadata_view as md where md.id = r.target_id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and md.lang = _lang LIMIT 1)
END) as accessres,
i.ids as acdhid
from root_data as rd
left join relations as r on rd.id = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
left join identifiers as i on i.id = rd.id and (i.ids LIKE CAST('%/id.acdh.oeaw.ac.at/%' as varchar) and i.ids NOT LIKE CAST('%/id.acdh.oeaw.ac.at/uuid/%' as varchar) )
where rd.title is not null;
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

DROP FUNCTION gui.detail_view_func(text, text);
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
    from child_ids as ci order by ci.orderid;
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
            where r.property = ANY (_rdftype)
            and i.ids = _parentid
    ) select * from ids		
);
	
RETURN QUERY
	select count(ci.id)
	from child_ids as ci;
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
        breadcrumbdata as bd;
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
        where 
        mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isMemberOf'
        and mv.value = _repoid 
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
select mv.id, mv.property, mv.value, mv.lang
from ids as i 
left join metadata_view as mv on mv.id = i.id
where mv.property in ('https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', 'https://vocabs.acdh.oeaw.ac.at/schema#hasAlternativeTitle' )and LOWER(mv.value) like '%' ||_searchStr || '%';
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
        and mv.type not in ('http://www.w3.org/2001/XMLSchema#integer', 'http://www.w3.org/2001/XMLSchema#long', 'http://www.w3.org/2001/XMLSchema#number')
);
RETURN QUERY
	select 
	DISTINCT(iv.id), iv.property, mv.value 
	from inverseIds as iv
	left join metadata_view as mv on mv.id = iv.id
	where mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle';
	--and mv.lang = _lang;
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
    left join metadata_view as mv on mv.id = od.id and mv.type = 'ID' and mv.value like 'https://vocabs.acdh%';
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
FROM count_main_collections as c;
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
    where 
    mv.property in (
        'https://vocabs.acdh.oeaw.ac.at/schema#isDerivedPublicationOf',		
        'https://vocabs.acdh.oeaw.ac.at/schema#isContinuedBy',
        'https://vocabs.acdh.oeaw.ac.at/schema#isDocumentedBy',
        'https://vocabs.acdh.oeaw.ac.at/schema#isSourceOf'
    ) 
    and mv.value = _identifier
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
        'https://vocabs.acdh.oeaw.ac.at/schema#hasSource'
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
                /* check the english title, if we dont have then get the german */
                (CASE 
                    WHEN 
                    (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint)  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = 'en' LIMIT 1) IS NULL
                    THEN
                    (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint)  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = 'de' LIMIT 1)
                    ELSE
                    (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = 'en' LIMIT 1)
                END) 
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
** select * from  gui.search_full_func('*', ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#Person' ],  '%', 'en',  '10', '0',  'desc', 'title' )
**/
DROP FUNCTION gui.search_full_func(text, text[], text, text, text, text, text, text, bool);
CREATE FUNCTION gui.search_full_func(_searchstr text DEFAULT '', _acdhtype text[] DEFAULT '{}', _acdhyears text DEFAULT '', _lang text DEFAULT 'en', _limit text DEFAULT '10', _page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'title', _binarySearch bool DEFAULT FALSE )
  RETURNS table (id bigint, title text, avDate timestamp, description text, accesres text, titleimage text, acdhtype text, cnt bigint, headline text)
AS $func$
DECLARE	
    _lang2 text := 'de';
	_lang3 text := 'und';
    limitint bigint := cast ( _limit as bigint);
    pageint bigint := cast ( _page as bigint);

BEGIN
--remove the tables if they are exists
DROP TABLE IF EXISTS title_data;
DROP TABLE IF EXISTS type_data;
DROP TABLE IF EXISTS years_data;

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


--check the search strings in title/description and binary content
CASE 
    WHEN (_searchstr <> '' ) IS TRUE THEN
        RAISE NOTICE USING MESSAGE =  'we have search string';
        --we have text search
        DROP TABLE IF EXISTS title_data;
        CREATE TEMPORARY TABLE title_data AS (
            WITH title_data as (
                SELECT 
                    DISTINCT(fts.id),
                    fts.property,
                    CASE WHEN fts.property = 'BINARY' THEN
                        COALESCE(
                            (select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
                            (select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
                            (select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
                        )
                        ELSE 
                        fts.raw
                    END as raw,
                    CASE WHEN fts.property = 'BINARY' THEN
                        ts_headline('english', REGEXP_REPLACE(fts.raw, '\s', ' ', 'g'), to_tsquery(_searchstr), 'MaxFragments=3,MaxWords=15,MinWords=8')
                    ELSE '' 
                    END as headline
                FROM full_text_search as fts 
                WHERE
                (
                    CASE WHEN (_binarySearch IS TRUE) THEN
                        (fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and websearch_to_tsquery('simple', _searchstr) @@ fts.segments )
                        OR (fts.property = 'BINARY' and websearch_to_tsquery('simple', _searchstr) @@ fts.segments )
                    ELSE
                        (fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and websearch_to_tsquery('simple', _searchstr) @@ fts.segments )							
                    END
                )
                UNION
                    SELECT 
                        DISTINCT(fts2.id), 
                        fts2.property,
                        fts2.raw,
                        '' as headline
                    FROM full_text_search as fts2 
                    WHERE
                        (
                            (fts2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and websearch_to_tsquery('simple', _searchstr) @@ fts2.segments )
                        ) limit 10000
				
            ) select * from title_data
	);	   
    ELSE
        RAISE NOTICE USING MESSAGE =  'we dont have string search else';
END CASE;

-- check the acdh type
CASE 
    WHEN _acdhtype  = '{}' THEN
        --we dont have type definied so all type will be searchable.
        RAISE NOTICE USING MESSAGE =  'we have title table but not the type';
    WHEN _acdhtype  != '{}' THEN
        -- we have type definied
        CASE
		WHEN (_searchstr <> '') IS TRUE  THEN
            RAISE NOTICE USING MESSAGE =  'we have title table and also the type  - type';
            DROP TABLE IF EXISTS type_data;
            CREATE TEMPORARY TABLE type_data AS (
                WITH type_data as (
                    SELECT 
                        DISTINCT(fts.id),
                        fts.property,
                        fts.raw,
                        td.headline
                    FROM title_data as td
                    LEFT JOIN full_text_search as fts on td.id = fts.id
                    WHERE
                    (
                        fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                        and 
                        fts.raw  = ANY (_acdhtype)
                    ) limit 10000
                ) select * from type_data
            );
		
        ELSE
            RAISE NOTICE USING MESSAGE =  'we DONT have title table  - type';
            DROP TABLE IF EXISTS type_data;
            CREATE TEMPORARY TABLE type_data AS (
                WITH type_data as (
                    SELECT 
                        DISTINCT(fts.id),
                        fts.property,
                        fts.raw,
                        '' as headline					
                    from full_text_search as fts
                    where
                    (
                        fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                        and 
                        fts.raw  = ANY (_acdhtype)
                    ) limit 10000
                ) select * from type_data
            );	
        END CASE;
        RAISE NOTICE USING MESSAGE =  'we have type';	
END CASE;
--union the title and the 

-- get the years
CASE 
    WHEN (_acdhyears <> '') IS TRUE THEN
        RAISE NOTICE USING MESSAGE =  'we have years';
        if (SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE lower(table_name) = 'type_data')) then
            RAISE NOTICE USING MESSAGE =  'we have type table  - years';
            DROP TABLE IF EXISTS years_data;
            CREATE TEMPORARY TABLE years_data AS (
                WITH years_data_r as (
                    SELECT 
                        DISTINCT(fts.id),
                        fts.property,
                        fts.raw,
                        td.headline as headline
                    FROM type_data as td
                    LEFT JOIN full_text_search as fts on fts.id = td.id
                    WHERE
                    (
                        (fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
						 TO_CHAR(TO_TIMESTAMP(fts.raw, 'YYYY'), 'YYYY')  similar to  _acdhyears )
                    )	
                ) select * from years_data_r
            );	
        elseif ( (_searchstr <> '') IS TRUE  and _searchstr != '*' and
		 (select exists( SELECT 1 FROM information_schema.tables WHERE lower(table_name) = 'title_data')) ) then	
            RAISE NOTICE USING MESSAGE =  'we have title table - years';
            DROP TABLE IF EXISTS years_data;
            CREATE TEMPORARY TABLE years_data AS (
                WITH years_data_r as (
                    SELECT 
                        DISTINCT(fts.id),
                        fts.property,
                        fts.raw,
                        td.headline
                    FROM title_data as td
                    LEFT JOIN full_text_search as fts on fts.id = td.id
                    WHERE
                    (
                        (fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
                       TO_CHAR(TO_TIMESTAMP(fts.raw, 'YYYY'), 'YYYY')  similar to  _acdhyears )
                    )	
                ) select * from years_data_r
            );
		ELSE
			RAISE NOTICE USING MESSAGE =  'we have just years';
            DROP TABLE IF EXISTS years_data;
            CREATE TEMPORARY TABLE years_data AS (
                WITH years_data_r as (
                    SELECT 
                        DISTINCT(fts.id),
                        fts.property,
                        fts.raw,
                        '' as headline
                    FROM full_text_search as fts 
                    WHERE
                    (
                        (fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
                        TO_CHAR(TO_TIMESTAMP(fts.raw, 'YYYY'), 'YYYY')  similar to  _acdhyears )
                    )	limit 10000
                ) select * from years_data_r
            );
        END IF;
    WHEN (_acdhyears <> '') IS NOT TRUE THEN
        RAISE NOTICE USING MESSAGE =  'we DONT have years';
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

if (
SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE lower(table_name) = 'years_data')) then
    RAISE NOTICE USING MESSAGE =  'final years';
    DROP TABLE IF EXISTS final_data;
    CREATE TEMPORARY TABLE final_data AS (
        WITH final_data as (
            select 
                yd.id, 
                COALESCE(
                    (select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
                    (select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
                    (select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang3 limit 1),
					(select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
                ) as title,
                yd.raw as avdate,
                COALESCE(
                    (select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
                    (select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
                    (select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang3 limit 1),
					(select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' limit 1)
                ) as description,
                COALESCE(
                    (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = yd.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang limit 1),	
                    (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = yd.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang2 limit 1),
                    (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = yd.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang3 limit 1),
					(select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = yd.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' limit 1)
                ) as accessres,
                (select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isTitleImageOf'limit 1) as titleimage,	
                (select mv.value from metadata_view as mv where mv.id = yd.id and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'limit 1) as acdhtype,
                yd.headline
            from years_data as yd
        )select * from final_data
    );	
elseif (SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE lower(table_name) = 'type_data')) then	
    RAISE NOTICE USING MESSAGE =  'final type';
    DROP TABLE IF EXISTS final_data;
	RAISE NOTICE USING MESSAGE =  'final data create';
    CREATE TEMPORARY TABLE final_data AS (
        WITH final_data as (
            select 
                td.id,
                COALESCE(
                    (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
                    (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1),
                    (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang3 limit 1),
					(select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
                ) as title,
                (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate,
                COALESCE(
                    (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
                    (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
                    (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang3 limit 1),
					(select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' limit 1)
                ) as description,
                COALESCE(
                    (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = td.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang limit 1),	
                    (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = td.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang2 limit 1),
                    (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = td.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang3 limit 1),
					(select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = td.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' limit 1)
                ) as accessres,
                (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isTitleImageOf' limit 1) as titleimage,
                td.raw as acdhtype,
                td.headline
            from type_data as td
        )select * from final_data											
    );	
	RAISE NOTICE USING MESSAGE =  'final type final data table create';
else 
    RAISE NOTICE USING MESSAGE =  'final title';
    DROP TABLE IF EXISTS final_data;
    CREATE TEMPORARY TABLE final_data AS (
        WITH final_data as (
            select 
                td.id,
                td.raw as title,
                (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate,
                COALESCE(
                    (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
                    (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
                    (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang3 limit 1),
					(select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' limit 1)
                ) as description,
				COALESCE(
                    (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = td.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang limit 1),	
                    (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = td.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang2 limit 1),
                    (select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = td.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and acs.lang = _lang3 limit 1),
					(select acs.value from metadata_view as mv left join accessres as acs on acs.id = CAST(mv.value as BIGINT) where mv.id = td.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' limit 1)
                ) as accessres,
                (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isTitleImageOf' limit 1) as titleimage,
                (select mv.value from metadata_view as mv where mv.id = td.id and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'limit 1) as acdhtype,
                td.headline
            from title_data as td
        )select * from final_data
    );
END IF;

DROP TABLE IF EXISTS count_data;
CREATE TEMPORARY TABLE count_data AS (
    select count(*) as cnt from final_data as cfd where cfd.title is not null
);

RETURN QUERY
    select 
        fd.id, fd.title, CAST(fd.avdate as timestamp) as avdate, fd.description, fd.accessres, fd.titleimage, fd.acdhtype, (select cd.cnt from count_data as cd) as cnt, fd.headline
    from final_data as fd
    where fd.title is not null
    order by  
        (CASE WHEN _orderby = 'asc' THEN (CASE WHEN _orderby_prop = 'title' THEN fd.title WHEN _orderby_prop = 'type' THEN fd.acdhtype ELSE fd.avdate END) END) ASC,
        (CASE WHEN _orderby_prop = 'title' THEN fd.title WHEN _orderby_prop = 'type' THEN fd.acdhtype  ELSE fd.avdate END) DESC
    limit limitint
    offset pageint;
END
$func$
LANGUAGE 'plpgsql';


/*
* -------------------------------------------------------------------------------------------------
*/

/**
* OLD SEARCH SQL section
*/

/*
* types count
* _acdhType = the array of the properties what we want to use during the search -> ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#Collection', 'https://vocabs.acdh.oeaw.ac.at/schema#Resource']
* _acdhyears -> the selected years as a string for example => '2020 or 2019'
*/
DROP FUNCTION gui.search_count_types_view_func(text[], text, text);
CREATE FUNCTION gui.search_count_types_view_func(_acdhtype text[], _lang text DEFAULT 'en', _acdhyears text DEFAULT '')
  RETURNS table (id bigint)
AS $func$
DECLARE 
	_lang2 text := 'de';
BEGIN
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
DROP TABLE IF EXISTS  typeids;
CREATE TEMP TABLE typeids AS (        
    WITH ids AS (
        SELECT 
            DISTINCT fts.id,
            COALESCE(
                    (select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
                    (select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1)
            ) as title,
            (select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate
        FROM full_text_search as fts
        WHERE  
        (
            fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
            and 
            fts.raw  = ANY (_acdhtype)
        )
    ) select * from ids
);

DROP TABLE IF EXISTS  typeidsFiltered;
CREATE TEMP TABLE typeidsFiltered AS (   
WITH ids2 AS (    
select DISTINCT(i.id), i.title, i.avdate from typeids as i
left join full_text_search as fts on fts.id = i.id
WHERE
	CASE 
	WHEN 
		(_acdhyears <> '') IS TRUE  THEN
		(fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
		fts.raw similar to  _acdhyears )
	WHEN (_acdhyears <> '') IS NOT TRUE THEN
		(fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and	
		fts.raw IS NOT NULL)
	END
	order by i.id
	) select * from ids2 
);

RETURN QUERY
select 
Count(tf.id)
from typeidsFiltered as tf;
END
$func$
LANGUAGE 'plpgsql';

/**
* Search types
* _acdhType = the array of the properties what we want to use during the search -> ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#Collection', 'https://vocabs.acdh.oeaw.ac.at/schema#Resource']
* _acdhyears -> the selected years as a string for example => '2020 or 2019'
* _limit _page _orderby _orderby_prop is for the paging
*/
DROP FUNCTION gui.search_types_view_func(text[], text, text, text, text, text, text);
CREATE FUNCTION gui.search_types_view_func(_acdhtype text[], _lang text DEFAULT 'en', _limit text DEFAULT '10', _page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'avdate',  _acdhyears text DEFAULT '')
  RETURNS table (id bigint, title text, avDate timestamp, description text, accesres text, titleimage text, acdhtype text)
AS $func$
DECLARE 
	_lang2 text := 'de';
	limitint bigint := cast ( _limit as bigint);
	pageint bigint := cast ( _page as bigint);
BEGIN
RAISE NOTICE USING MESSAGE = _acdhtype;
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
DROP TABLE IF EXISTS  typeids;
CREATE TEMP TABLE typeids AS (        
WITH ids AS (
		SELECT DISTINCT fts.id,
		COALESCE(
			(select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
			(select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1)
		)
		 as title,
		(select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate
		FROM full_text_search as fts
		WHERE  
			(
			 fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
			and 
			fts.raw  = ANY (_acdhtype)
			)
	) select * from ids
);

DROP TABLE IF EXISTS  typeidsFiltered;
CREATE TEMP TABLE typeidsFiltered AS (   
WITH ids2 AS (    
select DISTINCT(i.id), i.title, i.avdate from typeids as i
left join full_text_search as fts on fts.id = i.id
WHERE
	i.title is not null and 
	CASE 
	WHEN 
		(_acdhyears <> '') IS TRUE  THEN
		(fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
		fts.raw similar to  _acdhyears )
	WHEN (_acdhyears <> '') IS NOT TRUE THEN
		(fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and	
		fts.raw IS NOT NULL)
	END
	order by i.id
	) select * from ids2 
	order by  
		(CASE WHEN _orderby = 'asc' THEN (CASE WHEN _orderby_prop = 'title' THEN ids2.title ELSE ids2.avdate END) END) ASC,
         (CASE WHEN _orderby_prop = 'title' THEN ids2.title ELSE ids2.avdate END) DESC
		limit limitint
		offset pageint
);
													  
													  
DROP TABLE IF EXISTS  typesFinal;
CREATE TEMP TABLE typesFinal AS (   
WITH ids3 AS (  
	select tf.*,
	COALESCE(
			(select mv.value from metadata_view as mv where mv.id = tf.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
			(select mv.value from metadata_view as mv where mv.id = tf.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1)
		) description,
	(select mv.value from metadata_view as mv where tf.id = mv.id and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv.value like '%vocabs.%'  limit 1) as acdhtype,
	(CASE WHEN 
        (select mv2.value from metadata_view as mv left join metadata_view as mv2 on mv2.id = CAST(mv.value as BIGINT) where mv.id = tf.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1) IS NULL
    THEN
        (select mv2.value from metadata_view as mv left join metadata_view as mv2 on mv2.id = CAST(mv.value as BIGINT) where mv.id = tf.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1)
    ELSE
        (select mv2.value from metadata_view as mv left join metadata_view as mv2 on mv2.id = CAST(mv.value as BIGINT) where mv.id = tf.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1)
	END) as accessres,
	(select mv.value from metadata_view as mv where tf.id = mv.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' limit 1) as titleimage
	from typeidsFiltered as tf
	) select * from ids3
);

RETURN QUERY
select 
tf.id, tf.title, CAST(tf.avdate as timestamp), tf.description, tf.accessres, tf.titleimage, tf.acdhtype
from typesFinal as tf
where tf.title is not null;
END
$func$
LANGUAGE 'plpgsql';

/**
* Search years and types
*/
DROP FUNCTION gui.search_years_view_func(text, text, text, text, text, text, text[]);
CREATE FUNCTION gui.search_years_view_func(_acdhyears text, _lang text DEFAULT 'en', _limit text DEFAULT '10', _page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'avdate',  _acdhtype text[] DEFAULT '{}')
  RETURNS table (id bigint, title text, avDate timestamp, description text, accesres text, titleimage text, acdhtype text)
AS $func$
DECLARE 
	_lang2 text := 'de';
	limitint bigint := cast ( _limit as bigint);
	pageint bigint := cast ( _page as bigint);
BEGIN
RAISE NOTICE USING MESSAGE = _acdhyears;
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
DROP TABLE IF EXISTS yearsids;
CREATE TEMP TABLE yearsids AS (        
WITH ids AS (
	SELECT DISTINCT fts.id,
	COALESCE(
		(select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
		(select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1)
	)as title,
	(select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate
	FROM full_text_search as fts
	WHERE 
	(fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' 
	and
	websearch_to_tsquery('simple', _acdhyears) @@ segments )
	) select * from ids
);


DROP TABLE IF EXISTS  yearsidsFiltered;
CREATE TEMP TABLE yearsidsFiltered AS (   
WITH ids2 AS (  
	select DISTINCT(i.id),i.title, i.avdate from yearsids as i
	left join full_text_search as fts on fts.id = i.id
	WHERE
		CASE WHEN 
			_acdhtype  = '{}' THEN
			(fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
			fts.raw LIKE 'https://vocabs.acdh.oeaw.ac.at/schema#%')
		WHEN _acdhtype  != '{}' THEN
			(fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
			fts.raw = ANY (_acdhtype) )
		END
) select * from ids2 
	order by  
		(CASE WHEN _orderby = 'asc' THEN (CASE WHEN _orderby_prop = 'title' THEN ids2.title ELSE ids2.avdate END) END) ASC,
         (CASE WHEN _orderby_prop = 'title' THEN ids2.title ELSE ids2.avdate END) DESC
		limit limitint
		offset pageint
);

DROP TABLE IF EXISTS  yearsFinal;
CREATE TEMP TABLE yearsFinal AS (   
WITH ids3 AS (  
	select yf.*,
	COALESCE(
		(select mv.value from metadata_view as mv where mv.id = yf.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
		(select mv.value from metadata_view as mv where mv.id = yf.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1)
	) description,
	(select mv.value from metadata_view as mv where yf.id = mv.id and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv.value like '%vocabs.%'  limit 1) as acdhtype,
	(CASE WHEN 
            (select mv2.value from metadata_view as mv left join metadata_view as mv2 on mv2.id = CAST(mv.value as BIGINT) where mv.id = yf.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1) IS NULL
        THEN
            (select mv2.value from metadata_view as mv left join metadata_view as mv2 on mv2.id = CAST(mv.value as BIGINT) where mv.id = yf.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1)
        ELSE
            (select mv2.value from metadata_view as mv left join metadata_view as mv2 on mv2.id = CAST(mv.value as BIGINT) where mv.id = yf.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1)
	END) as accessres,
	(select mv.value from metadata_view as mv where yf.id = mv.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' limit 1) as titleimage
	from yearsidsFiltered as yf
	) select * from ids3
);

RETURN QUERY
select 
yf.id, yf.title, CAST(yf.avdate as timestamp), yf.description, yf.accessres, yf.titleimage, yf.acdhtype
from yearsFinal as yf;
END
$func$
LANGUAGE 'plpgsql';


/**
* Search words types and years
*/
DROP FUNCTION gui.search_words_view_func(text, text, text, text, text, text, text[], text);
CREATE FUNCTION gui.search_words_view_func(_searchstr text, _lang text DEFAULT 'en', _limit text DEFAULT '10', _page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'avdate', _rdftype text[] DEFAULT '{}', _acdhyears text DEFAULT '')
  RETURNS table (id bigint, title text, avDate timestamp, description text, accesres text, titleimage text, acdhtype text)
AS $func$

DECLARE	
	_lang2 text := 'de';
	limitint bigint := cast ( _limit as bigint);
	pageint bigint := cast ( _page as bigint);
BEGIN
--RAISE NOTICE USING MESSAGE = _rdftype;
--RAISE NOTICE USING MESSAGE = _acdhyears;
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;

DROP TABLE IF EXISTS  wordsids;
CREATE TEMP TABLE wordsids AS (        
WITH ids AS (
	SELECT DISTINCT fts.id,
	COALESCE(
		(select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
		(select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1)
	)
	 as title,
	(select mv.value from metadata_view as mv where mv.id = fts.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate
	FROM full_text_search as fts
	WHERE 
	websearch_to_tsquery('simple', _searchstr) @@ segments  
		AND 
		(
		 fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' 
		or 
		fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription'
		)

	) select DISTINCT(ids.id), ids.title, ids.avdate from ids where ids.title is not null
);

DROP TABLE IF EXISTS  wordsidsFiltered;
CREATE TEMP TABLE wordsidsFiltered AS (   
WITH ids2 AS (   
	Select t1.id, t1.title, t1.avdate
	FROM (
		select DISTINCT(i.id),i.title, i.avdate from wordsids as i
		left join full_text_search as fts on fts.id = i.id
		WHERE
			CASE WHEN 
				_rdftype  = '{}' THEN
				(fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
				fts.raw LIKE 'https://vocabs.acdh.oeaw.ac.at/schema#%')
			WHEN _rdftype  != '{}' THEN
				(fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
				fts.raw = ANY (_rdftype) )
			END) as t1
	INNER JOIN (
		select DISTINCT(i.id), i.title, i.avdate from wordsids as i
		left join full_text_search as fts3 on fts3.id = i.id
		where
			CASE WHEN 
				(_acdhyears <> '') IS TRUE  THEN
				(fts3.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
				fts3.raw similar to  _acdhyears )
			WHEN (_acdhyears <> '') IS NOT TRUE THEN
				(fts3.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and	
				fts3.raw IS NOT NULL)
			END
	) as t2 on t1.id = t2.id
) select * from ids2 
	order by  
		(CASE WHEN _orderby = 'asc' THEN (CASE WHEN _orderby_prop = 'title' THEN ids2.title ELSE ids2.avdate END) END) ASC,
         (CASE WHEN _orderby_prop = 'title' THEN ids2.title ELSE ids2.avdate END) DESC
		limit limitint
		offset pageint
);

DROP TABLE IF EXISTS  wordsFinal;
CREATE TEMP TABLE wordsFinal AS (   
WITH ids3 AS (  
	select wf.*,
	COALESCE(
			(select mv.value from metadata_view as mv where mv.id = wf.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
			(select mv.value from metadata_view as mv where mv.id = wf.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1)
		) description,
	(select mv.value from metadata_view as mv where wf.id = mv.id and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv.value like '%vocabs.%'  limit 1) as acdhtype,
	(CASE WHEN 
            (select mv2.value from metadata_view as mv left join metadata_view as mv2 on mv2.id = CAST(mv.value as BIGINT) where mv.id = wf.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1) IS NULL
        THEN
            (select mv2.value from metadata_view as mv left join metadata_view as mv2 on mv2.id = CAST(mv.value as BIGINT) where mv.id = wf.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1)
        ELSE
            (select mv2.value from metadata_view as mv left join metadata_view as mv2 on mv2.id = CAST(mv.value as BIGINT) where mv.id = wf.id and mv.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1)
	END) as accessres,
	(select mv.value from metadata_view as mv where wf.id = mv.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' limit 1) as titleimage
	from wordsidsFiltered as wf
	) select * from ids3
);

RETURN QUERY
select 
wf.id, wf.title, CAST(wf.avdate as timestamp), wf.description, wf.accessres, wf.titleimage, wf.acdhtype
from wordsFinal as wf;
END
$func$
LANGUAGE 'plpgsql';



/**
** YEARS SEARCH COUNT
**/
DROP FUNCTION gui.search_count_years_view_func(text, text, text[]);
CREATE FUNCTION gui.search_count_years_view_func(_acdhyears text, _lang text DEFAULT 'en', _acdhtype text[] DEFAULT '{}')
RETURNS table (id bigint)
AS $func$
DECLARE	
	_lang2 text := 'de';
	_lang text := 'en';
	
	BEGIN

DROP TABLE IF EXISTS  yearsids;
CREATE TEMP TABLE yearsids AS (        
WITH ids AS (
	SELECT DISTINCT fts.id
	FROM full_text_search as fts
	WHERE 
	(fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' 
	and
	websearch_to_tsquery('simple', _acdhyears) @@ segments )
	) select * from ids
	);
	
DROP TABLE IF EXISTS  yearsidsFiltered;
CREATE TEMP TABLE yearsidsFiltered AS (   
WITH ids2 AS (    
	select DISTINCT(i.id) from yearsids as i
	left join full_text_search as fts on fts.id = i.id
	WHERE
		CASE WHEN 
			_acdhtype  = '{}' THEN
			(fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
			fts.raw LIKE 'https://vocabs.acdh.oeaw.ac.at/schema#%')
		WHEN _acdhtype  != '{}' THEN
			(fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
			fts.raw = ANY (_acdhtype) )
		END
	) select * from ids2
);
	
RETURN QUERY
select 
Count(yf.id)
from yearsidsFiltered as yf;
END
$func$
LANGUAGE 'plpgsql';


/**
** WORD TYPE SEARCH COUNT
**/
DROP FUNCTION gui.search_count_words_view_func(text, text, text[], text);
CREATE FUNCTION gui.search_count_words_view_func(_searchstr text, _lang text DEFAULT 'en', _rdftype text[] DEFAULT '{}', _acdhyears text DEFAULT '')
  RETURNS table (id bigint)
AS $func$

DECLARE	
	_lang2 text := 'de';
	_lang text := 'en';
	
	BEGIN

DROP TABLE IF EXISTS  wordsids;
CREATE TEMP TABLE wordsids AS (        
WITH ids AS (
		SELECT DISTINCT fts.id
		FROM full_text_search as fts
		WHERE 
		websearch_to_tsquery('simple', _searchstr) @@ segments  
			AND 
			(
			 fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' 
			or 
			fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription'
			)
        ) select ids.id from ids
	);
	
DROP TABLE IF EXISTS  wordsidsFiltered;
CREATE TEMP TABLE wordsidsFiltered AS (   
WITH ids2 AS (    
	Select t1.id
	FROM (
		select DISTINCT(i.id) from wordsids as i
		left join full_text_search as fts on fts.id = i.id
		WHERE
			CASE WHEN 
				_rdftype  = '{}' THEN
				(fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
				fts.raw LIKE 'https://vocabs.acdh.oeaw.ac.at/schema#%')
			WHEN _rdftype  != '{}' THEN
				(fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
				fts.raw = ANY (_rdftype) )
			END) as t1
	INNER JOIN (
		select DISTINCT(i.id) from wordsids as i
		left join full_text_search as fts3 on fts3.id = i.id
		where
			CASE WHEN 
				(_acdhyears <> '') IS TRUE  THEN
				(fts3.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
				fts3.raw similar to  _acdhyears )
			WHEN (_acdhyears <> '') IS NOT TRUE THEN
				(fts3.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and	
				fts3.raw IS NOT NULL)
			END) as t2 on t1.id = t2.id
	) select * from ids2
);
	
RETURN QUERY
select 
Count(wf.id)
from wordsidsFiltered as wf;
END
$func$
LANGUAGE 'plpgsql';