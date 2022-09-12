@ECHO OFF
docker run --rm -it --init -v %cd%:/var/attlaz -w /var/attlaz hq.attlaz.com:2498/php7_4:1.1.0 blackfire --client-id=937d2f5f-b4b3-4a0c-9111-6d72d55f4ab0 --client-token=53fe7c1ec3b02fabb2ae25e1fada39a887952345eec2e3575a64de4a9659934a --ignore-exit-status run bin/console %*
