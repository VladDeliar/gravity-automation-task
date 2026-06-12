#!/bin/sh
# Тестовий сабміт CF7-форми зсередини WP-контейнера (чистий UTF-8).
curl -s -X POST "http://localhost/?rest_route=/contact-form-7/v1/contact-forms/6/feedback" \
  -F "_wpcf7=6" -F "_wpcf7_version=6.1.6" -F "_wpcf7_locale=uk" -F "_wpcf7_unit_tag=wpcf7-f6-o1" \
  -F "name=Марія з Сайту" -F "phone=+380631234567" -F "object_type=Квартира" \
  -F "area=65" -F "stage=Готово" -F "interest[]=Wireless" -F "interest[]=Smart home" \
  -F "comment=надіслано через CF7-форму WordPress" -F "source=gravity-wordpress"
echo ""
