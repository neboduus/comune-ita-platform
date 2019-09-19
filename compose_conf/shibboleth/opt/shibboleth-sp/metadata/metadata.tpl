<!--
This is example metadata only. Do *NOT* supply it as is without review,
and do *NOT* provide it in real time to your partners.
 -->
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" ID="_4b2c246044fb6c46b958b249abd8e34979d71bc4" entityID="https://%PROJECT_URL%">

    <md:Extensions xmlns:alg="urn:oasis:names:tc:SAML:metadata:algsupport">
        <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha512"/>
        <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#sha384"/>
        <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
        <alg:DigestMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#sha224"/>
        <alg:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha512"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha384"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha224"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha512"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha384"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2009/xmldsig11#dsa-sha256"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha1"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1"/>
        <alg:SigningMethod Algorithm="http://www.w3.org/2000/09/xmldsig#dsa-sha1"/>
    </md:Extensions>

    <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol urn:oasis:names:tc:SAML:1.1:protocol urn:oasis:names:tc:SAML:1.0:protocol">
        <md:Extensions>
            <init:RequestInitiator xmlns:init="urn:oasis:names:tc:SAML:profiles:SSO:request-init" Binding="urn:oasis:names:tc:SAML:profiles:SSO:request-init" Location="https://%PROJECT_URL%/Shibboleth.sso/Login"/>
        </md:Extensions>
        <md:KeyDescriptor>
            <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
                <ds:KeyName>sdc</ds:KeyName>
                <ds:X509Data>
                    <ds:X509SubjectName>CN=sdc</ds:X509SubjectName>
                    <ds:X509Certificate>MIID0DCCAjigAwIBAgIJAOvNFkBr922rMA0GCSqGSIb3DQEBCwUAMA4xDDAKBgNV
                        BAMTA3NkYzAeFw0xNjA5MjIxNjA5MDBaFw0yNjA5MjAxNjA5MDBaMA4xDDAKBgNV
                        BAMTA3NkYzCCAaIwDQYJKoZIhvcNAQEBBQADggGPADCCAYoCggGBAMIW+iUDkAbv
                        AYcUA0zCeSH3v/sHGm2wcYW2NE0Y95dH+R7SfuKaFYE5P9mBPn+5iQwycBco+hfz
                        HKxVbU1I2a1KGL08lIoFpTzJfun2GxunUqg3BOEaZlmeEBQDjgR2QXrFVe0R6eUg
                        CAxyjLwI0gwsxi6MEvXv19nRzGGjxVDnVoWjIU+2nMQcuXtcH7aWzFBAwU1AXvYN
                        bYY1OegJuI0Rwl9y6FgF/CmlL6fevT9Sjfjj4Ksf50SIGv37G1DjRCda71RZZy5C
                        NCisD9XAcTpN+jH/Oq96BesWpfJ4aHyl8O1isYc2tnfPAdsa/XEoY2/DxrgNb+XM
                        yUchcs3Ty/jGzpncIgoA5EJ15mxo32RPLRVEvh/XoBsJ/8Y+AWk3I94KGuPpxf/p
                        zEcoenIhP27TnMEdol7gyb0hnDbDgu3h97ksVoreWyNMnN9UPgvCq/xcyHpA7n6r
                        v820h0vgQo2uiiAkLHq+te/tiMDxXF2oqt4N8HACgL1t10NbuDmxJwIDAQABozEw
                        LzAOBgNVHREEBzAFggNzZGMwHQYDVR0OBBYEFM9P/Kl0rEIJNiwFzAg1BLFl4OoV
                        MA0GCSqGSIb3DQEBCwUAA4IBgQAGc/ZhYxEBJDPRRdB3VTluz45kNVt895wuE3dm
                        V6pkI05Hp5Xw2iMroLKeNb/VdSovqaIsmAsJo9Ryc8vOm5+JYQB+xLlASg+xDyTj
                        LBDRfTdayMuawzjVtVcwpt1jHSgw7WyXzU+0vgrNXHjXzGtpkLIsNrZqcwvn901v
                        xtbi7I94vziGo4ayQnWhYfb67PCCcltFguLp3FyvembL8GFrXZ7LXXKWhfjHbitC
                        UQGl6bJx8YkdPf9JH8sWpxVI77p2SNxwWs0WR3OwQr/dYeH57M2luKVDTXl5FlQ1
                        269yyw32yAL+/qYW34k5Aozj4+YNAdGnC/0zV0PcBz77jQLhUzq603FDpCpDiZff
                        hVOcBiqjzC6j7coMl8G/TejBz249aE++a6S1aPES/s+RrwwNQxb/ll8W4OSjUig0
                        lRCp4rZkWzv64QGFNd/yfMMrnO6EfkwEko3HDA41IMEPS3bXHKvGy12JD7Xtuyf4
                        rLC7rkfMZEvZ1N9F7EasAPvvJWM=
                    </ds:X509Certificate>
                </ds:X509Data>
            </ds:KeyInfo>
            <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes128-gcm"/>
            <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes192-gcm"/>
            <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#aes256-gcm"/>
            <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes128-cbc"/>
            <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes192-cbc"/>
            <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#aes256-cbc"/>
            <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#tripledes-cbc"/>
            <md:EncryptionMethod Algorithm="http://www.w3.org/2009/xmlenc11#rsa-oaep"/>
            <md:EncryptionMethod Algorithm="http://www.w3.org/2001/04/xmlenc#rsa-oaep-mgf1p"/>
        </md:KeyDescriptor>
        <md:ArtifactResolutionService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://%PROJECT_URL%/Shibboleth.sso/Artifact/SOAP" index="1"/>
        <md:ArtifactResolutionService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://%PROJECT_URL%/Shibboleth.sso/Artifact/SOAP" index="2"/>
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://%PROJECT_URL%/Shibboleth.sso/SLO/SOAP"/>
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://%PROJECT_URL%/Shibboleth.sso/SLO/Redirect"/>
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://%PROJECT_URL%/Shibboleth.sso/SLO/POST"/>
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://%PROJECT_URL%/Shibboleth.sso/SLO/Artifact"/>
        <md:ManageNameIDService Binding="urn:oasis:names:tc:SAML:2.0:bindings:SOAP" Location="https://%PROJECT_URL%/Shibboleth.sso/NIM/SOAP"/>
        <md:ManageNameIDService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://%PROJECT_URL%/Shibboleth.sso/NIM/Redirect"/>
        <md:ManageNameIDService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://%PROJECT_URL%/Shibboleth.sso/NIM/POST"/>
        <md:ManageNameIDService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://%PROJECT_URL%/Shibboleth.sso/NIM/Artifact"/>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://%PROJECT_URL%/Shibboleth.sso/SAML2/POST" index="1"/>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST-SimpleSign" Location="https://%PROJECT_URL%/Shibboleth.sso/SAML2/POST-SimpleSign" index="2"/>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://%PROJECT_URL%/Shibboleth.sso/SAML2/Artifact" index="3"/>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:PAOS" Location="https://%PROJECT_URL%/Shibboleth.sso/SAML2/ECP" index="4"/>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:browser-post" Location="https://%PROJECT_URL%/Shibboleth.sso/SAML/POST" index="5"/>
        <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:1.0:profiles:artifact-01" Location="https://%PROJECT_URL%/Shibboleth.sso/SAML/Artifact" index="6"/>
    </md:SPSSODescriptor>

</md:EntityDescriptor>