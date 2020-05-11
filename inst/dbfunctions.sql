/**
* COUNT THE ROOTS
**/
CREATE OR REPLACE FUNCTION gui.count_root_views_func()
  RETURNS table (id bigint)
AS $func$
DECLARE 
BEGIN	

RETURN QUERY
	select COUNT(DISTINCT(r.id))
	from metadata as m
	left join relations as r on r.id = m.id
	where
		m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
		and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
		and r.property != 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'	
		and NOT EXISTS ( 
			SELECT 1 from relations as r2 where r2.id = m.id  
				and r2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
		);
END
$func$
LANGUAGE 'plpgsql';	

/*
* ROOT VIEW FUNCTION 
*/
CREATE OR REPLACREATE OR REPLACE FUNCTION gui.root_views_func(_lang text DEFAULT 'en')
  RETURNS table (id bigint, title text, titleimage text, description text, avDate timestamp, accesres text )
AS $func$
DECLARE 
	_lang2 text := 'de';
BEGIN
--RAISE NOTICE USING MESSAGE = _lang;
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
RETURN QUERY
WITH root_data as (
	select DISTINCT(r.id) as id,
	(CASE WHEN 
		(select md.value from metadata_view as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and md.lang = _lang LIMIT 1) IS NULL
	THEN
		(select md.value from metadata_view as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and md.lang = _lang2 LIMIT 1)
	ELSE
	 	(select md.value from metadata_view as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and md.lang = _lang LIMIT 1)
	 END) as title,
	(select md.value from metadata_view as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' LIMIT 1) as titleImage,
	(CASE WHEN 
		(select md.value from metadata_view as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and md.lang = _lang LIMIT 1) IS NULL
	THEN
		(select md.value from metadata_view as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and md.lang = _lang2 LIMIT 1)
	ELSE
	 	(select md.value from metadata_view as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and md.lang = _lang LIMIT 1)
	 END) as description,
	CAST((select md.value from metadata as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' LIMIT 1) as timestamp)as avdate
	from metadata as m
	left join relations as r on r.id = m.id
	where
		m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
		and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
		and r.property != 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'	
		and NOT EXISTS ( 
			SELECT 1 from relations as r2 where r2.id = m.id  
				and r2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
		)
) select 
rd.id, rd.title, rd.titleimage, rd.description, rd.avdate,
(select mv.value from metadata_view as mv where mv.id = r.target_id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.value like 'http%')
from root_data as rd
left join relations as r on rd.id = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
where rd.title is not null;
END
$func$
LANGUAGE 'plpgsql';

/*
* DETAIL VIEW FUNCTION 
*/
CREATE OR REPLACE FUNCTION gui.detail_view_func(_identifier text, _lang text DEFAULT 'en')
    RETURNS table (id bigint, property text, type text, value text, relvalue text, acdhid text, accessRestriction text, language text )
    
AS $func$
DECLARE
	_lang2 text := 'de';
	_main_id bigint := (select i.id from identifiers as i where i.ids =_identifier);
BEGIN
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;

	DROP TABLE IF EXISTS detail_meta;
	CREATE TEMPORARY TABLE detail_meta AS (
		select mv.id, mv.property, mv.type, mv.value, mv.lang
		from metadata_view as mv 
		where mv.id = _main_id				
		union
		select m.id, m.property, m.type, m.value, m.lang
		from metadata as m 
		where m.id = _main_id
	);

	DROP TABLE IF EXISTS detail_meta_rel;
	CREATE TEMPORARY TABLE detail_meta_rel AS (
	select DISTINCT(CAST(m.id as VARCHAR)), m.value,  i.ids as acdhId, m.lang
	from metadata as m
	left join detail_meta as dm on CAST(dm.value as INT) = m.id and m.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
	left join identifiers as i on i.id = m.id and i.ids LIKE CAST('%/id.acdh.oeaw.ac.at/uuid/%' as varchar)
	where dm.type = 'REL' );
	
	RETURN QUERY
	select dm.id, dm.property, dm.type, 
	dm.value, 
	dmr.value as relvalue, 
	dmr.acdhid,
	CASE WHEN dm.property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' THEN dmr.value
	ELSE ''
	END,
	( CASE WHEN dm.lang IS NULL THEN dmr.lang ELSE dm.lang end ) as language
	from detail_meta as dm
	left join detail_meta_rel as dmr on dmr.id = dm.value
	order by property; 
END
$func$
LANGUAGE 'plpgsql';

/*
* COLLECTION VIEW FUNCTION 
*/
CREATE OR REPLACE FUNCTION gui.collection_views_func(_pid text, _lang text DEFAULT 'en' )
    RETURNS table (mainid bigint, parentid bigint, title text, accesres text, license text, binarysize text, filename text, locationpath text, depth integer)
AS $func$
BEGIN

DROP TABLE IF EXISTS accessres;
	CREATE TEMP TABLE accessres AS (
	select 
	distinct(r.target_id) as accessid , mv.value,
	mv.lang
	from relations as r
	left join metadata_view as mv on mv.id = r.target_id
	where r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
	and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle'
	and mv.lang = _lang
);

DROP TABLE IF EXISTS colldata;
	CREATE TEMPORARY TABLE colldata(mainid bigint, parentid bigint, title text, accesres text, license text, binarysize text, filename text, locationpath text, depth integer);
	INSERT INTO colldata( 
		WITH RECURSIVE subordinates AS (
		   SELECT
			  mv.id as mainid,
			CAST(mv.value as bigint) as parentid, 
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLicense' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasBinarySize' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasFilename' and id = mv.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLocationPath' and id = mv.id limit 1),
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
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLicense' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasBinarySize' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasFilename' and id = mv2.id limit 1),
			(select value from metadata_view where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasLocationPath' and id = mv2.id limit 1),
				depthval + 1 
			  FROM
				 metadata_view as mv2
			  INNER JOIN subordinates s ON s.mainid = CAST(mv2.value as bigint) and  mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf' 
		) select * from subordinates
	);

RETURN QUERY
   select mv.mainid, mv.parentid, mv.title, ar.value, mv.license, mv.binarysize, mv.filename, mv.locationpath, mv.depth 
   from 
   colldata as mv
   left join accessres as ar on CAST(mv.accesres as integer) = ar.accessid;
END
$func$
LANGUAGE 'plpgsql';

/*
* CHILD VIEW FUNCTION 
*/

CREATE OR REPLACE FUNCTION gui.child_views_func(_parentid text, _limit text, _page text, _orderby text, _orderprop text, _lang text DEFAULT 'en', _rdftype text[] DEFAULT '{}' )
    RETURNS table (id bigint, title text, avDate timestamp, description text, accesres text, titleimage text, acdhtype text)
AS $func$
	DECLARE _lang2 text := 'de';
	DECLARE limitint bigint := cast ( _limit as bigint);
	DECLARE pageint bigint := cast ( _page as bigint);
BEGIN
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;

DROP TABLE IF EXISTS child_ids;
	CREATE TEMP TABLE child_ids AS(
	WITH ids AS (
		select 
			r.id,
			COALESCE(
				(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang limit 1),	
				(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang2 limit 1)
			) as title,
			(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' limit 1) as avdate,
			COALESCE(
				(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang limit 1),	
				(select mv.value from metadata_view as mv where mv.id = r.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang2 limit 1)
			) description,
			(select mv.value from relations as r2 left join metadata_view as mv on r2.target_id = mv.id where r.id = r2.id and r2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and
			mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and value like 'http%') as accessres,
			(select mv.value from metadata_view as mv where r.id = mv.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' limit 1) as titleimage,
			(select mv.value from metadata_view as mv where r.id = mv.id and mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv.value like '%vocabs.%'  limit 1) as acdhtype
		from relations as r
		left join identifiers as i on i.id = r.target_id 
		left join metadata_view as mv on mv.id = r.id
		where r.property = ANY (_rdftype)
		and mv.property = _orderprop
		and i.ids = _parentid
		order by  
		(CASE WHEN _orderby = 'asc' THEN mv.value END) ASC,
         mv.value DESC
		limit limitint
		offset pageint
	) select * from ids		
);
	
RETURN QUERY
	select ci.id, ci.title, CAST(ci.avdate as timestamp), ci.description, ci.accessres, ci.titleimage, ci.acdhtype
	from child_ids as ci;
END
$func$
LANGUAGE 'plpgsql';

/*
* CHILD VIEW SUM
*/

CREATE OR REPLACE FUNCTION gui.child_sum_views_func(_parentid text, _rdftype text[] DEFAULT '{}')
    RETURNS table (countid bigint)
AS $func$

BEGIN
    DROP TABLE IF EXISTS child_ids;
    CREATE TEMP TABLE child_ids AS(
    WITH ids AS (
            select 
                    r.id			
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
* BREADCRUMB VIEW METADATA FUNCTION 
*/
CREATE OR REPLACE FUNCTION gui.breadcrumb_view_func(_pid text, _lang text DEFAULT 'en' )
    RETURNS table (mainid bigint, parentid bigint, parentTitle text, depth integer)
AS $func$
BEGIN

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
        bd.mainid, bd.parentid, 
	mv.value,
        bd.depth 
    from 
        breadcrumbdata as bd
    left join 
        metadata_view as mv on mv.id = bd.parentid 
     where  
        mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle';
END
$func$
LANGUAGE 'plpgsql';

/**
* SEARCH SQL
*/

/**
** types count
**/
CREATE OR REPLACE FUNCTION gui.search_count_types_view_func(_acdhtype text[], _lang text DEFAULT 'en', _acdhyears text DEFAULT '')
  RETURNS table (id bigint)
AS $func$
DECLARE 
	_lang2 text := 'de';
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
*/
CREATE OR REPLACE FUNCTION gui.search_types_view_func(_acdhtype text[], _lang text DEFAULT 'en', _limit text DEFAULT '10', _page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'avdate',  _acdhyears text DEFAULT '')
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
	(select mv.value from relations as r left join metadata_view as mv on r.target_id = mv.id where tf.id = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and
mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and value like 'http%') as accessres,
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
CREATE OR REPLACE FUNCTION gui.search_years_view_func(_acdhyears text, _lang text DEFAULT 'en', _limit text DEFAULT '10', _page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'avdate',  _acdhtype text[] DEFAULT '{}')
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
	(select mv.value from relations as r left join metadata_view as mv on r.target_id = mv.id where yf.id = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and value like 'http%') as accessres,
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
CREATE OR REPLACE FUNCTION gui.search_words_view_func(_searchstr text, _lang text DEFAULT 'en', _limit text DEFAULT '10', _page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'avdate', _rdftype text[] DEFAULT '{}', _acdhyears text DEFAULT '')
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
	(select mv.value from relations as r left join metadata_view as mv on r.target_id = mv.id where wf.id = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' and
mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and value like 'http%') as accessres,
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
** WORD TYPE SEARCH COUNT
**/
CREATE OR REPLACE FUNCTION gui.search_count_words_view_func(_searchstr text, _lang text DEFAULT 'en', _rdftype text[] DEFAULT '{}', _acdhyears text DEFAULT '')
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

/**
* API CALLS
**/

/**
* API getDATA
**/

CREATE OR REPLACE FUNCTION gui.apiGetData(_class text, _searchStr text)
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
CREATE OR REPLACE FUNCTION gui.inverse_data_func(_identifier text, _lang text DEFAULT 'en')
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

CREATE OR REPLACE FUNCTION gui.ontology_func(_lang text DEFAULT 'en')
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
CREATE OR REPLACE FUNCTION gui.count_binaries_collection_func()
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
			and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
			and r.property != 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'	
			and NOT EXISTS ( 
				SELECT 1 from relations as r2 where r2.id = m.id  
					and r2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
			)
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