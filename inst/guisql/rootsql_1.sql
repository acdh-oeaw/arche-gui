--select * from root_views_func('0', '10', 'date', 'en');
--DROP FUNCTION public.root_views_func(_lang text);
CREATE OR REPLACE FUNCTION gui.root_views_func(_lang text DEFAULT 'en')
  RETURNS table (id bigint, title text, avDate timestamp, description text, accresres text, titleimage text)
AS $func$
BEGIN
--RAISE NOTICE USING MESSAGE = _lang;

--get all root ids
DROP TABLE IF EXISTS  rootids;
CREATE TEMP TABLE rootids AS (
	select DISTINCT(r.id) as rootid,
	(select mt.value from metadata as mt where mt.id = r.id and mt.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' LIMIT 1) as title,
	CAST((select md.value from metadata as md where md.id = r.id and md.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' LIMIT 1) as timestamp)as avdate
	from metadata as m
	left join relations as r on r.id = m.id
	where
		m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
		and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
		and r.property != 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'	
		and r.id NOT IN ( 
			SELECT DISTINCT(r.id) from metadata as m left join relations as r on r.id = m.id
			where
				m.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
				and m.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
				and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
		)
);

DROP TABLE IF EXISTS rootDescriptions;
CREATE TEMP TABLE rootDescriptions AS (
	select ri.rootid, mv.value
	from rootids as ri
	left join 
	metadata_view as mv on ri.rootid = mv.id
	where 
	mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription' and mv.lang = _lang
);


DROP TABLE IF EXISTS rootAccesRes;
CREATE TEMP TABLE rootAccesRes AS (
select ri.rootid, mv.value 
from rootids as ri
left join relations as r on ri.rootid = r.id and r.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction'
left join metadata_view as mv on r.target_id = mv.id
where
mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle' and mv.lang = _lang
);

RETURN QUERY
select  ri.rootid, ri.title, ri.avdate,  rd.value as description, ra.value as accesres,
(select mv.value from metadata_view as mv where mv.id = ri.rootid and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage' ) as titleimage
from rootids as ri
left join rootAccesRes as ra on ri.rootid  = ra.rootid
left join rootDescriptions as rd on ri.rootid  = rd.rootid;
END
$func$
LANGUAGE 'plpgsql';


select * from root_views_func('en') order by avdate desc;