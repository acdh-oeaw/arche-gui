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
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
        ) as title,	
        COALESCE(
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1),
            (select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' limit 1)
        ) as description,
        CAST((select mv.value from metadata_view as mv where mv.id = m.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' LIMIT 1) as timestamp)as avdate,
        (select mv.value from metadata_view as mv where mv.id = m.id and mv.type = 'ID' and mv.value LIKE CAST('%/id.acdh.oeaw.ac.at/%' as varchar) and mv.value NOT LIKE CAST('%/id.acdh.oeaw.ac.at/cmdi/%' as varchar) LIMIT 1) as acdhid
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
* _identifier => full repo api url, f.e.: https://arche-dev.acdh-dev.oeaw.ac.at/api/76704
* Because we supporting the 3rd party identifiers too, like vicav, etc
* execution time between: 140-171ms
*/
DROP FUNCTION IF EXISTS gui.detail_view_func(text, text);
CREATE FUNCTION gui.detail_view_func(_identifier text, _lang text DEFAULT 'en')
    RETURNS table (id bigint, property text, type text, value text, relvalue text, acdhid text, vocabsid text, accessRestriction text, language text, lastname text )     
AS $func$
DECLARE
    /* get the arche gui identifier */
    _main_id bigint := (select i.id from identifiers as i where i.ids =_identifier);
BEGIN
RETURN QUERY
    WITH dmetaRel as (
        Select 
            (CAST(main.id as INT)), main.property, main.type, main.value, '' as relvalue, '' as acdhid, '' as vocabsid, '' as accessrestriction, _lang as lang
        from (
            select 
                m.mid, m.id, m.property, m.type, _lang as lang, m.value
            from metadata as m
            where 
                m.id = _main_id and ((m.lang <> '') IS NOT TRUE or m.lang IN (_lang, 'und')  )
            UNION
            select 
                m3.mid, m3.id, m3.property, m3.type, m3.lang, m3.value
            from metadata as m3 
            where
                m3.id = _main_id and ( (m3.lang <> '') IS TRUE and m3.lang NOT IN (_lang, 'und')) 
                and not exists (
                    select *
                    from metadata as m
                    where m.id = _main_id and ((m.lang <> '') IS NOT TRUE or m.lang IN (_lang, 'und')) and m.property = m3.property
                )
            order by property
        ) as main
        UNION
        select 
            (CAST(rel.id as INT)) ,  rel.property, 'REL' as type, (CAST(rel.relid as VARCHAR)) as value, rel.value as relvalue, rel.acdhid, rel.vocabsid, '' as accessrestriction, _lang as lang
        FROM (
            select 
                DISTINCT on (CAST(m.id as VARCHAR)) m.id as relid, m.value, mv.property,  i.ids as acdhId, i2.ids as vocabsid, m.lang, mv.id as id 
            from metadata_view as mv 
            left join metadata as m on CAST(mv.value as INT) = m.id and m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
            left join identifiers as i on i.id = m.id and i.ids LIKE CAST('%.acdh.oeaw.ac.at/api/%' as varchar)
            left join identifiers as i2 on i2.id = m.id and i2.ids LIKE CAST('%vocabs.acdh.oeaw.ac.at/%' as varchar)
            where 
                mv.id = _main_id and mv.type = 'REL' and ((m.lang <> '') IS NOT TRUE or m.lang IN (_lang, 'und'))
            UNION
            select 
                DISTINCT on (CAST(m.id as VARCHAR)) m.id as relid, m.value, mv.property, i.ids as acdhId, i2.ids as vocabsid, m.lang, mv.id as id
            from metadata_view as mv 
            left join metadata as m on CAST(mv.value as INT) = m.id and m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
            left join identifiers as i on i.id = m.id and i.ids LIKE CAST('%.acdh.oeaw.ac.at/api/%' as varchar)
            left join identifiers as i2 on i2.id = m.id and i2.ids LIKE CAST('%vocabs.acdh.oeaw.ac.at/%' as varchar)
            where 
                mv.id = _main_id and mv.type = 'REL' and ((m.lang <> '') IS NOT TRUE or m.lang NOT IN (_lang, 'und'))
                and not exists (
                    select 
                        DISTINCT(CAST(m2.id as VARCHAR)), m2.value, mv2.property, m2.lang, '' as acdhId, '' as vocabsid, m2.lang
                    from metadata_view as mv2 
                    left join metadata as m2 on CAST(mv2.value as INT) = m2.id and m2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'			
                    where 
                        mv2.id = _main_id and mv2.type = 'REL' and ((m2.lang <> '') IS NOT TRUE or m2.lang IN (_lang, 'und')) and mv.property = mv2.property
                )
            order by value
        ) as rel
    )
    select 
	dmr.id, dmr.property, dmr.type, 
	dmr.value, 
	dmr.relvalue, 
	dmr.acdhid,
	dmr.vocabsid,
	dmr.accessrestriction,
	dmr.lang,
        (select ln.value from metadata_view as ln where ln.id = CAST(dmr.value as INT) and dmr.type='REL' and ln.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLastName' limit 1) as lastname
    from dmetaRel as dmr
    left join resources as rs on rs.id = dmr.id and rs.state = 'active'
    UNION
    Select 
        mv.id, mv.property, mv.type, mv.value, '' as relvalue, '' as acdhid, '' as vocabsid, '' as accessrestriction, mv.lang, ''
    from metadata_view as mv where mv.type = 'ID' and mv.id = _main_id
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
--DROP FUNCTION gui.child_views_func(text, text, text, text, text, text, text[] );
DROP FUNCTION gui.child_views_func(text, int, int, text, text, text, text[] );
CREATE FUNCTION gui.child_views_func(_parentid text, _limit int, _page int, _orderby text, _orderprop text, _lang text DEFAULT 'en',  _rdftype text[] DEFAULT '{}' )
    RETURNS table (id bigint, title text, avDate timestamp, description text, accesres text, titleimage text, acdhtype text, version text, orderid integer)
AS $func$    
    WITH t1 AS (
        SELECT id, row_number() OVER () AS orderid
        FROM (
            SELECT id, ordervalue, CASE _orderby WHEN 'desc' THEN -row_number() OVER () ELSE row_number() OVER () END AS orderid
            FROM (
                SELECT
                    r1.id, 
                    (array_agg(m1.value ORDER BY CASE m1.lang WHEN _lang THEN 0 WHEN 'en' THEN 1 ELSE 2 END))[1] AS ordervalue
                FROM
                    identifiers i
                    JOIN relations r1 ON r1.target_id = i.id AND r1.property = ANY (_rdftype)
                    JOIN metadata m1 ON r1.id = m1.id AND m1.property = _orderprop
                WHERE i.ids = _parentid
                GROUP BY 1
                ORDER BY 2
            ) t
            ORDER BY orderid
        ) t
        LIMIT _limit
        OFFSET _page
    )
    SELECT id, title, avdate, description, accesres, titleimage, acdhtype, version, orderid::int
    FROM (
        SELECT
            t4.id, orderid, title, description, acdhtype, mr3.value as version,
            m5.value_t AS avdate,
            '' AS titleimage, -- left for return type compatibility - the old code was always returning null as wrong property was in use,
            (array_agg(mr2.value ORDER BY CASE mr2.lang WHEN _lang THEN 0 WHEN 'en' THEN 1 ELSE 2 END))[1] AS accesres
        FROM
            (
                SELECT
                    t3.id, orderid, title, description,
                    (array_agg(m4.value))[1] AS acdhtype
                FROM
                    (
                        SELECT
                            t2.id, orderid, title,
                            (array_agg(m3.value ORDER BY CASE m3.lang WHEN _lang THEN 0 WHEN 'en' THEN 1 ELSE 2 END))[1] AS description
                        FROM
                            (
                                SELECT 
                                    t1.id, orderid,
                                    (array_agg(m2.value ORDER BY CASE m2.lang WHEN _lang THEN 0 WHEN 'en' THEN 1 ELSE 2 END))[1] AS title
                                FROM
                                    t1
                                    JOIN metadata m2 ON t1.id = m2.id AND m2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
                                GROUP BY 1, 2    
                            ) t2
                            LEFT JOIN metadata m3 ON t2.id = m3.id AND m3.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription'
                        GROUP BY 1, 2, 3
                    ) t3
                    JOIN metadata m4 ON t3.id = m4.id AND m4.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' AND m4.value LIKE 'https://vocabs.acdh.%'
                GROUP BY 1, 2, 3, 4
            ) t4
            JOIN metadata m5 ON t4.id = m5.id AND m5.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'
            LEFT JOIN relations r2 ON t4.id = r2.id AND r2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
            LEFT JOIN metadata mr2 ON r2.target_id = mr2.id AND mr2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
            LEFT JOIN metadata mr3 ON t4.id = mr3.id AND mr3.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasVersion'      
        GROUP BY 1, 2, 3, 4, 5, 6, 7
    ) t order by orderid
$func$
LANGUAGE 'sql';

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
DROP FUNCTION IF EXISTS gui.breadcrumb_view_func(bigint, text );
CREATE FUNCTION gui.breadcrumb_view_func(_pid bigint, _lang text DEFAULT 'en' )
    RETURNS table (mainid bigint, parentid bigint, parentTitle text, depth integer, direct_parent bigint, avdate date)
AS $func$
    with parents as (
        select *
        from get_relatives(_pid, 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf', 0)
        where n < 0
    )
    select 
        _pid as mainid, 
        p.id as parentid, 
        (array_agg(mv.value order by case mv.lang when _lang then 0 when 'en' then 1 else 2 end))[1] as parenttitle, 
        abs(p.n) as depth,
        (select r.target_id from relations as r where r.id = p.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' limit 1) as direct_parent,
        (select CAST(avd.value as DATE) from metadata_view as avd where avd.id = p.id and avd.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate') as avdate
    from 
        parents p
        join resources rs using (id)
        join metadata mv using (id)
    where
        rs.state = 'active'
        and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
    group by 1, 2, 4
$func$
LANGUAGE 'sql';

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
                (select mv2.value from metadata_view as mv2 where mv.id = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
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
DECLARE _lang2 text := 'de';

BEGIN
RETURN QUERY
WITH inverseIds AS (
	select 
    DISTINCT(mv.id), mv.property 
    from metadata_view as mv
    where 
    mv.value = _identifier
    and mv.property NOT IN ('https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' , 'https://vocabs.acdh.oeaw.ac.at/schema#hasPid')
    and mv.type not in ('http://www.w3.org/2001/XMLSchema#integer', 'http://www.w3.org/2001/XMLSchema#long', 'http://www.w3.org/2001/XMLSchema#number',
					   'http://www.w3.org/2001/XMLSchema#decimal', 'http://www.w3.org/2001/XMLSchema#nonNegativeInteger')
	) select 
        DISTINCT(iv.id), iv.property, 
        COALESCE(
            (select mv2.value from metadata_view as mv2 where mv2.id = iv.id  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang LIMIT 1),	
            (select mv2.value from metadata_view as mv2 where mv2.id = iv.id  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 LIMIT 1),
            (select mv2.value from metadata_view as mv2 where mv2.id = iv.id  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'  LIMIT 1)
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
        select 
            mv.id, 
            (array_agg(distinct mv3.value))[1] as title,
            (array_agg(distinct mv2.value))[1] as description, 
            (array_agg(distinct i.ids))[1] as type
        from
            metadata_view as mv
            left join metadata_view as mv2 using (id)
            left join metadata_view as mv3 using (id)
            join identifiers as i using (id)
        where 
            mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
            and substring(mv.value, 1, 1000) in ('http://www.w3.org/2002/07/owl#DatatypeProperty', 'http://www.w3.org/2002/07/owl#ObjectProperty')
            and mv2.property = 'http://www.w3.org/2000/01/rdf-schema#comment'
            and mv2.lang = _lang
            and mv3.property = 'http://www.w3.org/2004/02/skos/core#altLabel'
            and mv3.lang = _lang
            and i.ids like 'https://vocabs.acdh%'
        group by 1
        order by 1
$func$
LANGUAGE 'sql';

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
        (select mv2.value from metadata_view as mv2 where mv.id = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
    ) as title,
    mv.property,
    (select mv2.value from metadata_view as mv2 where mv2.id = mv.id and mv2.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv2.value like '%.oeaw.ac.at/%' LIMIT 1)
    from metadata_view as mv
    left join resources as rs on rs.id = mv.id 
    where 
    mv.property in (
        'https://vocabs.acdh.oeaw.ac.at/schema#isDerivedPublicationOf',		
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
        (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
    ) as title,
    mv.property,
    (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint) and mv2.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv2.value like '%.oeaw.ac.at/%' LIMIT 1)
    from metadata_view as mv
    where 
    mv.property IN ( 
        'https://vocabs.acdh.oeaw.ac.at/schema#isDerivedPublicationOf',
        'https://vocabs.acdh.oeaw.ac.at/schema#relation',
        'https://vocabs.acdh.oeaw.ac.at/schema#continues',
        'https://vocabs.acdh.oeaw.ac.at/schema#documents',
        'https://vocabs.acdh.oeaw.ac.at/schema#hasSource',
        'https://vocabs.acdh.oeaw.ac.at/schema#hasDerivedPublication',		
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
                    (select mv2.value from metadata_view as mv2 where mv2.id = CAST(mv.value as bigint)  and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' LIMIT 1)
                )
            ELSE '' 
        END as title, 
        mv.type, mv.value as key, count(mv.*) as cnt
    FROM public.metadata_view as mv
    WHERE 
        CASE WHEN _property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier' THEN
            mv.type = 'ID'
        ELSE
            mv.property = _property	
        END
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
DROP FUNCTION IF EXISTS gui.search_full_v3_func(text, text[], text, text, text, text, text, text, bool, text[]);
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
DROP TABLE IF EXISTS collection_data;

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

CREATE TEMPORARY TABLE collection_data (acdhid bigint, headline_title text, headline_desc text, headline_binary text);

--check the search strings in title/description and binary content
CASE 
    WHEN (_searchstr <> '' ) IS TRUE THEN
        RAISE NOTICE USING MESSAGE =  'search string query';
        INSERT INTO collection_data (acdhid, headline_title, headline_desc, headline_binary) select sd.id, sd.headline_title, sd.headline_desc, sd.headline_binary from gui.searchstrData_v3(_searchstr, _lang, _binarySearch) as sd order by sd.id;
    ELSE
        RAISE NOTICE USING MESSAGE =  'search string query skipped';
END CASE;
------------------------------------------------------------------------------- TYPE ----------------------------------------------
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
				) DELETE FROM collection_data cd
                WHERE NOT EXISTS
                ( SELECT 1 FROM type_data td WHERE td.id = cd.acdhid );
			WHEN ( _searchstr <> '' ) THEN
				RAISE NOTICE USING MESSAGE =  'collection empty but we have search params so we have to skip - type';	
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
------------------------------------------------------------------------------- Categories ----------------------------------------------
CASE 
    WHEN _category  = '{}' THEN
        --we dont have type definied so all type will be searchable.
        RAISE NOTICE USING MESSAGE =  '_category query skipped';
    WHEN _category  != '{}' THEN
        -- we have type definied
        RAISE NOTICE USING MESSAGE =  '_category query ';
        CASE 
            WHEN (select exists(select * from collection_data limit 1 ) ) IS TRUE THEN
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
				) 
                DELETE FROM collection_data cd
                WHERE NOT EXISTS
                ( SELECT 1 FROM category_data td WHERE td.id = cd.acdhid );
			WHEN (_acdhtype  != '{}' or _searchstr <> '' ) THEN
				RAISE NOTICE USING MESSAGE =  'collection empty but we have search params so we have to skip - category';
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
------------------------------------------------------------------------------- YEARS ----------------------------------------------
CASE 
    WHEN (_acdhyears <> '') IS TRUE THEN
        RAISE NOTICE USING MESSAGE =  'years query';
            CASE 
                WHEN (select exists(select * from collection_data limit 1 ) ) IS TRUE THEN
                    RAISE NOTICE USING MESSAGE =  'years query with have collection data';
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
                        ) 
                    DELETE FROM collection_data cd
                    WHERE NOT EXISTS
                    ( SELECT 1 FROM years_data yd WHERE yd.id = cd.acdhid );
			WHEN (_category  != '{}' or _acdhtype  != '{}' or _searchstr <> '' ) THEN
                   RAISE NOTICE USING MESSAGE =  'collection empty but we have search params so we have to skip';
		   	ELSE
				RAISE NOTICE USING MESSAGE =  'there are no searching string, just years, insert to collection';
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
    WHEN (_acdhyears <> '') IS NOT TRUE THEN
        RAISE NOTICE USING MESSAGE =  'years query skipped';
END CASE;

------------------------------------------------------------------------------- ACCESSRES ----------------------------------------------

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

------------------------------------------------------------------------------- FINAL ----------------------------------------------
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
    _searchstrId text := LOWER(REPLACE(REPLACE(_searchstr, 'https://', '/'), 'http://', '/'));
    _searchstrIdSlash text := CONCAT('/', _searchstrId);
    _searchStrIdText text := CONCAT(_searchstrId, ' or ', _searchstrIdSlash);
    _searchstr text := LOWER(REPLACE(_searchstr, ' ', ','));
    
BEGIN

DROP TABLE IF EXISTS std_data;
DROP TABLE IF EXISTS sb_data;
RAISE NOTICE USING MESSAGE =  _searchstr;
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
		UNION 
            SELECT 
                DISTINCT COALESCE(m.id, fts.iid) AS id,
                fts.raw as headline_title,
                '' as headline_desc,
                '' as headline_binary
            FROM full_text_search as fts 
            LEFT JOIN metadata as m on m.mid = fts.mid
            WHERE  
                 websearch_to_tsquery('simple', _searchStrIdText) @@ fts.segments and fts.raw LIKE 'http%'		
            limit 20000
            			
        ) select * from std_data
    );
    ELSE
	RAISE NOTICE USING MESSAGE =  'searchstring is empty';
END CASE;

CASE WHEN (_binarySearch IS TRUE) THEN
    CREATE TEMPORARY TABLE sb_data AS (
        WITH sb_data as (
            SELECT websearch_to_tsquery(_searchstr) AS query),
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
  RETURNS table (id bigint, title text, avDate timestamp, depth integer, version text, prevId bigint)
AS $func$
DECLARE	
    _lang2 text := 'de';
    _lang3 text := 'und';   
BEGIN
    IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
RETURN QUERY
    WITH RECURSIVE child_subordinates AS (
        SELECT
            mv.value,
            mv.id as prevId,
            1 as depthval
        FROM
            metadata_view as mv
        WHERE
            mv.id = CAST(_identifier as bigint)
            and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
        UNION
        SELECT
            mv2.value,
            mv2.id as prevId,
            depthval + 1
        FROM
            metadata_view mv2
        INNER JOIN child_subordinates s ON CAST(s.value as bigint) = mv2.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
    ),
    --get the parents
    parent_subordinates AS (
        SELECT
            mv.id,
            mv.id as prevId,
            -1 as depthval
        FROM
            metadata_view as mv
        WHERE
            mv.value = _identifier
            and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
        UNION
            SELECT
                mv2.id,
                mv2.id as prevId,
                depthval - 1
            FROM
                metadata_view mv2
            INNER JOIN parent_subordinates s ON s.id = CAST(mv2.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
    ) SELECT
        CAST(c.value as bigint),
        COALESCE(
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(c.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1),	
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(c.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1),
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(c.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
        ) as title,
        (select CAST(mv2.value as timestamp) from metadata_view as mv2 where  mv2.id = CAST(c.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'  limit 1) as avdate,
        c.depthval,
        (select mv2.value from metadata_view as mv2 where mv2.id = CAST(c.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasVersion' limit 1) as version,
        c.prevId
    FROM
        child_subordinates as c
    UNION
    Select 
        p.id,
        COALESCE(
            (select mv2.value from metadata_view as mv2 where mv2.id = p.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1),	
            (select mv2.value from metadata_view as mv2 where mv2.id = p.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1),
            (select mv2.value from metadata_view as mv2 where mv2.id = p.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
        ) as title,
        (select CAST(mv2.value as timestamp) from metadata_view as mv2 where  mv2.id = p.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'  limit 1) as avdate,
    p.depthval,
    (select mv2.value from metadata_view as mv2 where mv2.id = p.id and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasVersion' limit 1) as version,
    p.prevId
    from parent_subordinates as p
    UNION
    select
        CAST(_identifier as bigint) as id,
        COALESCE(
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(_identifier as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang limit 1),	
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(_identifier as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv2.lang = _lang2 limit 1),
            (select mv2.value from metadata_view as mv2 where mv2.id = CAST(_identifier as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' limit 1)
        ) as title,
        (select CAST(mv2.value as timestamp) from metadata_view as mv2 where  mv2.id = CAST(_identifier as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'  limit 1) as avdate,
        0,
        (select mv2.value from metadata_view as mv2 where mv2.id = CAST(_identifier as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasVersion' limit 1) as version,
        (select mv2.id from metadata_view as mv2 where mv2.value = _identifier and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf' limit 1) as prevId;
END
$func$
LANGUAGE 'plpgsql';
