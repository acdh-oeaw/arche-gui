/*
* ROOT VIEW FUNCTION 
*/
CREATE OR REPLACE FUNCTION gui.root_views_func(_lang text DEFAULT 'en')
  RETURNS table (id bigint, title text, avDate timestamp, description text, accesres text, titleimage text, language text)
AS $func$
DECLARE 
	_lang2 text := 'de';
BEGIN
--RAISE NOTICE USING MESSAGE = _lang;
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
--get all root ids
DROP TABLE IF EXISTS  rootids;
CREATE TEMP TABLE rootids AS (
	select DISTINCT(r.id) as rootid,
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
);

DROP TABLE IF EXISTS rootTitles;
CREATE TEMP TABLE rootTitles AS (
	select ri.rootid, mv.value, mv.lang
	from rootids as ri
	left join 
	metadata_view as mv on ri.rootid = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' 
);

DROP TABLE IF EXISTS rootDescriptions;
CREATE TEMP TABLE rootDescriptions AS (
	select ri.rootid, mv.value, mv.lang
	from rootids as ri
	left join 
	metadata_view as mv on ri.rootid = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' 
);


DROP TABLE IF EXISTS rootAccesRes;
CREATE TEMP TABLE rootAccesRes AS (
select ri.rootid, mv.value 
from rootids as ri
left join relations as r on ri.rootid = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
left join metadata_view as mv on r.target_id = mv.id
where
mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and value like 'http%'
);

RETURN QUERY
select ri.rootid,
( CASE WHEN rt.value IS NULL THEN (select rt.value from rootTitles as rt where rt.rootid = ri.rootid and lang = _lang2 limit 1) ELSE rt.value end ) as title,
ri.avdate, 
( CASE WHEN rd.value IS NULL THEN (select rd2.value from rootDescriptions as rd2 where rd2.rootid = ri.rootid and lang = _lang2 limit 1) ELSE rd.value end ) as description,
ra.value as accesres, 
(select mv.value from metadata_view as mv where mv.id = ri.rootid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' ) as titleimage,
null as language
from rootids as ri
left join rootDescriptions as rd on rd.rootid = ri.rootid and rd.lang = _lang
left join rootTitles as rt on rt.rootid = ri.rootid and rt.lang = _lang
left join rootAccesRes as ra on ri.rootid  = ra.rootid;
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

CREATE OR REPLACE FUNCTION gui.child_views_func(_parentid text, _limit text, _page text, _orderby text, _orderprop text, _lang text DEFAULT 'en' )
    RETURNS table (childid bigint, property text, value text, order_prop text, order_val text, orderid bigint, lang text)
AS $func$
DECLARE limitint bigint := cast ( _limit as bigint);
DECLARE pageint bigint := cast ( _page as bigint);

BEGIN

RAISE NOTICE USING MESSAGE = _lang;
RAISE NOTICE USING MESSAGE = _parentid;
RAISE NOTICE USING MESSAGE = _orderby;
RAISE NOTICE USING MESSAGE = _orderprop;
RAISE NOTICE USING MESSAGE = _limit;
RAISE NOTICE USING MESSAGE = _page;

	/* get child ids */
	DROP TABLE IF EXISTS child_ids;
	CREATE TEMPORARY TABLE child_ids(childid bigint NOT NULL, prop text NOT NULL, value text NOT NULL);
	INSERT INTO child_ids( 
		select 
			r.id as childid, mv.property, mv.value
		from relations as r
		left join identifiers as i on i.id = r.target_id 
		left join metadata_view as mv on mv.id = r.id
		where r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
		and mv.property = _orderprop
		and i.ids = _parentid
		order by  
		(CASE WHEN _orderby = 'asc' THEN mv.value END) ASC,
         mv.value DESC
		limit limitint
		offset pageint
	); 
	ALTER TABLE child_ids ADD COLUMN id SERIAL PRIMARY KEY;
	
RETURN QUERY
		select 
		CAST(ci.childid as bigint),  mv.property, 
		CASE 
		WHEN mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction' 
		THEN  
		(select mv2.value from metadata_view as mv2 where id = CAST(mv.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' LIMIT 1) 
		ELSE 
		mv.value 
		END,
		ci.prop, ci.value, CAST(ci.id as bigint),
		mv.lang
		from child_ids as ci
		left join metadata_view as mv on mv.id = ci.childid
		where 
		mv.property in (
			'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle',
			'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription',
			'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage',
			'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction',
			'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate',
			'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
		) 
		Order by ci.id
		;
END
$func$
LANGUAGE 'plpgsql';

/*
* CHILD VIEW SUM
*/

CREATE OR REPLACE FUNCTION gui.child_sum_views_func(_parentid text)
    RETURNS table (childid bigint)
AS $func$

BEGIN
	/* get child ids */
	DROP TABLE IF EXISTS child_ids;
	CREATE TEMPORARY TABLE child_ids(childid bigint NOT NULL);
	INSERT INTO child_ids( 
		select 
			r.id as childid
		from relations as r
		left join identifiers as i on i.id = r.target_id 
		where r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
		and i.ids = _parentid
	); 
	ALTER TABLE child_ids ADD COLUMN id SERIAL PRIMARY KEY;
	
RETURN QUERY
		select 
		COUNT(*)
		from child_ids 
		;
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
* Search types
*/
CREATE OR REPLACE FUNCTION gui.search_types_view_func(_acdhtype text[], _lang text DEFAULT 'en', _acdhyear text[] DEFAULT '{}')
  RETURNS table (id bigint, title text, avDate date, description text, accesres text, titleimage text, acdhtype text)
AS $func$
DECLARE 
	_lang2 text := 'de';
BEGIN
RAISE NOTICE USING MESSAGE = _acdhtype;
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
DROP TABLE IF EXISTS  typeids;
CREATE TEMP TABLE typeids AS (        
WITH ids AS (
	SELECT t1.id FROM (
		SELECT DISTINCT fts.id
		FROM full_text_search as fts
		WHERE  
			(
			 fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
			and 
			fts.raw  = ANY (_acdhtype)
			)
		 
        ) t1   
	) select * from ids
);


DROP TABLE IF EXISTS titles;
CREATE TEMP TABLE titles AS (
	select ti.id, mv.value, mv.lang
	from typeids as ti
	left join 
	metadata_view as mv on ti.id = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' 
	
);

DROP TABLE IF EXISTS descriptions;
CREATE TEMP TABLE descriptions AS (
	select ti.id, mv.value, mv.lang
	from typeids as ti
	left join 
	metadata_view as mv on ti.id = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' 
);


DROP TABLE IF EXISTS availableDate;
CREATE TEMP TABLE availableDate AS (
	select ti.id, mv.value
	from typeids as ti
	left join 
	metadata_view as mv on ti.id = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' 
);

DROP TABLE IF EXISTS rdftype;
CREATE TEMP TABLE rdftype AS (
	select ti.id, mv.value
	from typeids as ti
	left join 
	metadata_view as mv on ti.id = mv.id
	where 
	mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
);

DROP TABLE IF EXISTS accesRes;
CREATE TEMP TABLE accesRes AS (
select ti.id, mv.value 
from typeids as ti
left join relations as r on ti.id = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
left join metadata_view as mv on r.target_id = mv.id
where
mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and value like 'http%'
);

RETURN QUERY
select DISTINCT(ti.id),
( CASE WHEN rt.value IS NULL THEN (select rt.value from titles as rt where rt.id = ti.id and lang = _lang2 limit 1) ELSE rt.value end ) as title,
( CASE WHEN CAST(ad.value as DATE) IS NULL THEN ( NULL) ELSE CAST(ad.value as DATE) end ) as avdate,
( CASE WHEN rd.value IS NULL THEN (select rd2.value from descriptions as rd2 where rd2.id = ti.id and lang = _lang2 limit 1) ELSE rd.value end ) as description,
ra.value as accesres, 
(select mv.value from metadata_view as mv where mv.id = ti.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' ) as titleimage,
rdt.value as acdhtype
from typeids as ti
left join descriptions as rd on rd.id = ti.id and rd.lang = _lang
left join titles as rt on rt.id = ti.id  and rt.lang = _lang
left join accesRes as ra on ti.id  = ra.id
left join availableDate as ad on ti.id  = ad.id
left join rdftype as rdt on ti.id  = rdt.id;
END
$func$
LANGUAGE 'plpgsql';

/**
* Search years and types
*/
CREATE OR REPLACE FUNCTION gui.search_years_view_func(_acdhyears text, _lang text DEFAULT 'en', _acdhtype text[] DEFAULT '{}')
  RETURNS table (id bigint, title text, avDate date, description text, accesres text, titleimage text, acdhtype text)
AS $func$
DECLARE 
	_lang2 text := 'de';
BEGIN
RAISE NOTICE USING MESSAGE = _acdhyears;
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
DROP TABLE IF EXISTS yearsids;
CREATE TEMP TABLE yearsids AS (        
WITH ids AS (
	SELECT t1.id FROM (
		SELECT DISTINCT fts.id
		FROM full_text_search as fts
		WHERE 
		(fts.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' 
		and
		websearch_to_tsquery('simple', _acdhyears) @@ segments )
        ) t1   
	) select * from ids
);


IF _acdhtype != '{}' THEN
	RAISE NOTICE 'generate types table';
	DROP TABLE IF EXISTS yearsTypes;
	CREATE TEMP TABLE yearsTypes AS (        
		WITH ids AS (
		SELECT t1.id FROM (
			SELECT DISTINCT fts.id
			FROM full_text_search as fts
			right join yearsids as yi on yi.id = fts.id
			WHERE 
				 fts.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
				and 
				fts.raw  = ANY (_acdhtype)
			) t1   
		) select * from ids
	);
	DROP TABLE IF EXISTS resultIds;
	CREATE TEMP TABLE resultIds AS (   
		select yt.id from yearsTypes as yt
	);
ELSE
	RAISE NOTICE 'NO types table';
	DROP TABLE IF EXISTS resultIds;
	CREATE TEMP TABLE resultIds AS (   
		select yi.id from yearsids as yi
	);
END IF;

DROP TABLE IF EXISTS titles;
CREATE TEMP TABLE titles AS (
	select ri.id, mv.value, mv.lang
	from resultIds as ri
	left join 
	metadata_view as mv on ri.id = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' 
	
);

DROP TABLE IF EXISTS descriptions;
CREATE TEMP TABLE descriptions AS (
	select ri.id, mv.value, mv.lang
	from resultIds as ri
	left join 
	metadata_view as mv on ri.id = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' 
);


DROP TABLE IF EXISTS availableDate;
CREATE TEMP TABLE availableDate AS (
	select ri.id, mv.value
	from resultIds as ri
	left join 
	metadata_view as mv on ri.id = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' 
);

DROP TABLE IF EXISTS rdftype;
CREATE TEMP TABLE rdftype AS (
	select ri.id, mv.value
	from resultIds as ri
	left join 
	metadata_view as mv on ri.id = mv.id
	where 
	mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
);

DROP TABLE IF EXISTS accesRes;
CREATE TEMP TABLE accesRes AS (
select ri.id, mv.value 
from resultIds as ri
left join relations as r on ri.id = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
left join metadata_view as mv on r.target_id = mv.id
where
mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and value like 'http%'
);

RETURN QUERY
select DISTINCT(ri.id),
( CASE WHEN rt.value IS NULL THEN (select rt.value from titles as rt where rt.id = ri.id and lang = _lang2 limit 1) ELSE rt.value end ) as title,
( CASE WHEN CAST(ad.value as DATE) IS NULL THEN ( NULL) ELSE CAST(ad.value as DATE) end ) as avdate,
( CASE WHEN rd.value IS NULL THEN (select rd2.value from descriptions as rd2 where rd2.id = ri.id and lang = _lang2 limit 1) ELSE rd.value end ) as description,
ra.value as accesres, 
(select mv.value from metadata_view as mv where mv.id = ri.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' ) as titleimage,
rdt.value as acdhtype
from resultIds as ri
left join descriptions as rd on rd.id = ri.id and rd.lang = _lang
left join titles as rt on rt.id = ri.id and rt.lang = _lang
left join accesRes as ra on ri.id  = ra.id
left join availableDate as ad on ri.id  = ad.id
left join rdftype as rdt on ri.id  = rdt.id
where rt.value is not null ;
END

$func$
LANGUAGE 'plpgsql';

/**
* Search words types and years
*/
CREATE OR REPLACE FUNCTION gui.search_words_view_func(_searchstr text, _lang text DEFAULT 'en', _rdftype text[] DEFAULT '{}', _acdhyears text[] DEFAULT '{}')
  RETURNS table (id bigint, title text, avDate timestamp, description text, accesres text, titleimage text, acdhtype text)
AS $func$
DECLARE 
	_lang2 text := 'de';
BEGIN
RAISE NOTICE USING MESSAGE = _rdftype;
RAISE NOTICE USING MESSAGE = _acdhyears;
	IF _lang = 'de' THEN _lang2 = 'en'; ELSE _lang2 = 'de'; END IF;
	
DROP TABLE IF EXISTS  wordsids;
CREATE TEMP TABLE wordsids AS (        
WITH ids AS (
	SELECT t1.id FROM (
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
        ) t1   
	) select * from ids
);


DROP TABLE IF EXISTS filterTypes;
CREATE TEMP TABLE filterTypes AS (
	select DISTINCT(wi.id)
	from wordsids as wi
	left join 
	metadata_view as mv on wi.id = mv.id
	where 
	CASE WHEN 
		_rdftype  = '{}' THEN
		(mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
		mv.value LIKE 'https://vocabs.acdh.oeaw.ac.at/schema#%')
	ELSE 
		(mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and
		mv.value = ANY (_rdftype) )
	END
);

DROP TABLE IF EXISTS filterYears;
CREATE TEMP TABLE filterYears AS (
	select DISTINCT(wi.id)
	from wordsids as wi
	left join 
	metadata_view as mv on wi.id = mv.id
	where 
	CASE WHEN 
		_acdhyears  = '{}' THEN
		(mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and
		mv.value IS NOT NULL)
	ELSE 
		(mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' and	
		mv.value = ANY (_acdhyears))
	END
);


DROP TABLE IF EXISTS resultIds;
CREATE TEMP TABLE resultIds AS (
	select DISTINCT(ft.id)
	from filterTypes as ft
	inner join 
	filterYears as fy on fy.id = ft.id
);



DROP TABLE IF EXISTS titles;
CREATE TEMP TABLE titles AS (
	select ri.id, mv.value, mv.lang
	from resultIds as ri
	left join 
	metadata_view as mv on ri.id = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' 
);

DROP TABLE IF EXISTS descriptions;
CREATE TEMP TABLE descriptions AS (
	select ri.id, mv.value, mv.lang
	from resultIds as ri
	left join 
	metadata_view as mv on ri.id = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' 
);


DROP TABLE IF EXISTS availableDate;
CREATE TEMP TABLE availableDate AS (
	select ri.id, mv.value
	from resultIds as ri
	left join 
	metadata_view as mv on ri.id = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' 
);

DROP TABLE IF EXISTS rdftype;
CREATE TEMP TABLE rdftype AS (
	select ri.id, mv.value
	from resultIds as ri
	left join 
	metadata_view as mv on ri.id = mv.id
	where 
	mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
);


DROP TABLE IF EXISTS accesRes;
CREATE TEMP TABLE accesRes AS (
select ri.id, mv.value 
from resultIds as ri
left join relations as r on ri.id = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
left join metadata_view as mv on r.target_id = mv.id
where
mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and value like 'http%'
);

RETURN QUERY
select ri.id,
( CASE WHEN rt.value IS NULL THEN (select rt.value from titles as rt where rt.id = ri.id and lang = _lang2 limit 1) ELSE rt.value end ) as title,
( CASE WHEN CAST(ad.value as timestamp) IS NULL THEN ( NULL) ELSE CAST(ad.value as timestamp) end ) as avdate,
( CASE WHEN rd.value IS NULL THEN (select rd2.value from descriptions as rd2 where rd2.id = ri.id and lang = _lang2 limit 1) ELSE rd.value end ) as description,
ra.value as accesres, 
(select mv.value from metadata_view as mv where mv.id = ri.id and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' ) as titleimage,
rdt.value as acdhtype
from resultIds as ri
left join descriptions as rd on rd.id = ri.id and rd.lang = _lang
left join titles as rt on rt.id = ri.id and rt.lang = _lang
left join accesRes as ra on ri.id = ra.id
left join availableDate as ad on ri.id = ad.id
left join rdftype as rdt on ri.id  = rdt.id
;

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

